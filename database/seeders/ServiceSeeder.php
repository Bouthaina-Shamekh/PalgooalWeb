<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Service;
use App\Models\ServiceTranslation;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'icon' => '/assets/tamplate/images/icons/Shared-hosting.svg',
                'order' => 0,
                'url' => '#',
                'translations' => [
                    'ar' => [
                        'title' => 'الاستضافة المشتركة',
                        'description' => 'استضافة قوية واقتصادية لموقعك، مع شهادة SSL مجانية وسرعة تشغيل عالية.',
                    ],
                    'en' => [
                        'title' => 'Website Design',
                        'description' => 'Responsive and custom designs that reflect your brand and give a professional user experience.',
                    ],
                ],
            ],
            [
                'icon' => '/assets/tamplate/images/icons/wordpress-hosting.svg',
                'order' => 1,
                'url' => '#',
                'translations' => [
                    'ar' => [
                        'title' => 'استضافة ووردبريس',
                        'description' => 'احجز اسم موقعك بسهولة واختر من بين مجموعة واسعة من الامتدادات العالمية.',
                    ],
                    'en' => [
                        'title' => 'WordPress Hosting',
                        'description' => 'Easily register your domain name and choose from a wide variety of global extensions.',
                    ],
                ],
            ],
            [
                'icon' => '/assets/tamplate/images/icons/domains.svg',
                'order' => 2,
                'url' => '#',
                'translations' => [
                    'ar' => [
                        'title' => 'حجز اسم نطاق (دومين)',
                        'description' => 'تمتع بأداء عالٍ وأمان كامل لموقعك على ووردبريس مع دعم فني دائم.',
                    ],
                    'en' => [
                        'title' => 'Domain name reservation',
                        'description' => 'High-performance and secure hosting for your WordPress site with full-time support.',
                    ],
                ],
            ],
            [
                'icon' => '/assets/tamplate/images/icons/Website-design.svg',
                'order' => 3,
                'url' => '#',
                'translations' => [
                    'ar' => [
                        'title' => 'تصميم مواقع',
                        'description' => 'استضافة قوية واقتصادية لموقعك، مع شهادة SSL مجانية وسرعة تشغيل عالية.',
                    ],
                    'en' => [
                        'title' => 'Website design',
                        'description' => 'Reliable and affordable hosting with free SSL and fast performance.',
                    ],
                ],
            ],
            [
                'icon' => '/assets/tamplate/images/icons/Special-programming.svg',
                'order' => 4,
                'url' => '#',
                'translations' => [
                    'ar' => [
                        'title' => 'برمجيات خاصة',
                        'description' => 'استضافة قوية واقتصادية لموقعك، مع شهادة SSL مجانية وسرعة تشغيل عالية.',
                    ],
                    'en' => [
                        'title' => 'Special software',
                        'description' => 'Reliable and affordable hosting with free SSL and fast performance.',
                    ],
                ],
            ],
        ];

        foreach ($services as $serviceData) {
            $service = Service::create([
                'icon' => $serviceData['icon'],
                'order' => $serviceData['order'],
            ]);

            foreach ($serviceData['translations'] as $locale => $translation) {
                ServiceTranslation::create([
                    'service_id' => $service->id,
                    'locale' => $locale,
                    'title' => $translation['title'],
                    'description' => $translation['description'],
                ]);
            }
        }
    }
}

