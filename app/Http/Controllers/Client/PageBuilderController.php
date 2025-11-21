<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Tenancy\Subscription;
use App\Models\Tenancy\SubscriptionPage;
use App\Models\Tenancy\SubscriptionSection;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PageBuilderController extends Controller
{
    use AuthorizesRequests;

    protected function authorizeSubscription(Subscription $subscription, SubscriptionPage $page, Request $request): void
    {
        $client = $request->user('client');

        abort_unless($client && $subscription->client_id === $client->id, 403);

        $subscription->loadMissing('plan');

        if (
            ! $subscription->plan
            || $subscription->plan->plan_type !== Plan::TYPE_MULTI_TENANT
            || $subscription->provisioning_status !== Subscription::PROVISIONING_ACTIVE
            || $page->subscription_id !== $subscription->id
        ) {
            abort(404, 'Subscription is not an active multi-tenant.');
        }

        $this->authorize('manage', $subscription);
    }

    public function builder(Request $request, Subscription $subscription, SubscriptionPage $page)
    {
        $this->authorizeSubscription($subscription, $page, $request);

        $sections = $page->sections()
            ->with(['translations'])
            ->orderBy('sort_order')
            ->get();

        $selectedId = $request->query('section');
        $selectedSection = $sections->firstWhere('id', (int) $selectedId) ?: $sections->first();

        $blocks = config('template_blocks.restaurant', []);

        return view('client.subscriptions.pages.builder', [
            'subscription' => $subscription,
            'page' => $page,
            'sections' => $sections,
            'selectedSection' => $selectedSection,
            'blocks' => $blocks,
        ]);
    }

    public function reorder(Request $request, Subscription $subscription, SubscriptionPage $page)
    {
        $this->authorizeSubscription($subscription, $page, $request);

        $data = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer'],
        ]);

        DB::transaction(function () use ($data, $page) {
            foreach ($data['order'] as $index => $id) {
                SubscriptionSection::where('subscription_page_id', $page->id)
                    ->where('id', $id)
                    ->update(['sort_order' => $index + 1]);
            }
        });

        return response()->json(['ok' => true]);
    }

    public function addSection(Request $request, Subscription $subscription, SubscriptionPage $page)
    {
        $this->authorizeSubscription($subscription, $page, $request);

        $data = $request->validate([
            'block_key' => ['required', 'string'],
        ]);

        $blocks = config('template_blocks.restaurant', []);
        $block = $blocks[$data['block_key']] ?? null;

        if (! $block) {
            return back()->withErrors(['block_key' => __('Block not found')]);
        }

        DB::transaction(function () use ($block, $page) {
            $maxOrder = (int) $page->sections()->max('sort_order');
            $section = $page->sections()->create([
                'key' => $block['key'] ?? $block['type'],
                'type' => $block['type'],
                'variant' => $block['variant'] ?? null,
                'sort_order' => $maxOrder + 1,
            ]);

            $translations = $block['translations'] ?? [];
            foreach ($translations as $locale => $payload) {
                $section->translations()->create([
                    'locale' => $locale,
                    'title' => $payload['title'] ?? null,
                    'content' => $payload['content'] ?? null,
                ]);
            }
        });

        return back()->with('ok', __('Section added to page.'));
    }

    public function updateSection(Request $request, Subscription $subscription, SubscriptionSection $section)
    {
        $page = $section->page;
        $this->authorizeSubscription($subscription, $page, $request);

        $rules = [
            'title' => ['nullable', 'string', 'max:255'],
        ];

        if ($section->type === 'hero') {
            $rules = array_merge($rules, [
                'subtitle' => ['nullable', 'string', 'max:500'],
                'button_label' => ['nullable', 'string', 'max:255'],
                'button_url' => ['nullable', 'string', 'max:500'],
                'image' => ['nullable', 'string', 'max:2000'],
                'background_image_file' => ['nullable', 'image', 'max:2048'],
                'background_color' => ['nullable', 'string', 'max:20'],
                'text_color' => ['nullable', 'string', 'max:20'],
            ]);
        } elseif ($section->type === 'menu') {
            $rules = array_merge($rules, [
                'items' => ['nullable', 'array'],
                'items.*.name' => ['nullable', 'string', 'max:255'],
                'items.*.description' => ['nullable', 'string', 'max:1000'],
                'items.*.price' => ['nullable', 'string', 'max:50'],
                'background_color' => ['nullable', 'string', 'max:20'],
                'text_color' => ['nullable', 'string', 'max:20'],
            ]);
        } elseif ($section->type === 'testimonials') {
            $rules = array_merge($rules, [
                'items' => ['nullable', 'array'],
                'items.*.name' => ['nullable', 'string', 'max:255'],
                'items.*.text' => ['nullable', 'string', 'max:1000'],
                'items.*.rating' => ['nullable', 'integer', 'min:1', 'max:5'],
                'background_color' => ['nullable', 'string', 'max:20'],
                'text_color' => ['nullable', 'string', 'max:20'],
            ]);
        }

        $data = $request->validate($rules);

        $locale = app()->getLocale();
        $content = [];

        switch ($section->type) {
            case 'hero':
                $imageUrl = $data['image'] ?? null;
                if ($request->hasFile('background_image_file')) {
                    $imageUrl = $request->file('background_image_file')->store('tenant-sections', 'public');
                    $imageUrl = asset('storage/' . $imageUrl);
                }
                $content = [
                    'subtitle' => $data['subtitle'] ?? null,
                    'button_label' => $data['button_label'] ?? null,
                    'button_url' => $data['button_url'] ?? null,
                    'image' => $imageUrl,
                    'colors' => [
                        'background' => $data['background_color'] ?? null,
                        'text' => $data['text_color'] ?? null,
                    ],
                ];
                break;
            case 'menu':
                $items = [];
                foreach ($data['items'] ?? [] as $item) {
                    $clean = array_filter($item ?? [], fn($v) => $v !== null && $v !== '');
                    if (! empty($clean)) {
                        $items[] = [
                            'name' => $item['name'] ?? null,
                            'description' => $item['description'] ?? null,
                            'price' => $item['price'] ?? null,
                        ];
                    }
                }
                $content = [
                    'items' => $items,
                    'colors' => [
                        'background' => $data['background_color'] ?? null,
                        'text' => $data['text_color'] ?? null,
                    ],
                ];
                break;
            case 'testimonials':
                $items = [];
                foreach ($data['items'] ?? [] as $item) {
                    $clean = array_filter($item ?? [], fn($v) => $v !== null && $v !== '');
                    if (! empty($clean)) {
                        $items[] = [
                            'name' => $item['name'] ?? null,
                            'text' => $item['text'] ?? null,
                            'rating' => $item['rating'] ?? null,
                        ];
                    }
                }
                $content = [
                    'items' => $items,
                    'colors' => [
                        'background' => $data['background_color'] ?? null,
                        'text' => $data['text_color'] ?? null,
                    ],
                ];
                break;
            default:
                $content = $data['content'] ?? [];
                break;
        }

        $section->translations()->updateOrCreate(
            ['locale' => $locale],
            [
                'title' => $data['title'] ?? null,
                'content' => $content,
            ]
        );

        return back()->with('ok', __('Section updated.'));
    }
}
