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
                'icon' => 'icons/web-design.svg',
                'order' => 1,
                'translations' => [
                    'ar' => [
                        'title' => 'تصميم مواقع',
                        'description' => 'تصاميم مخصصة ومتجاوبة تعكس هوية مشروعك وتمنح الزائر تجربة احترافية.',
                    ],
                    'en' => [
                        'title' => 'Website Design',
                        'description' => 'Responsive and custom designs that reflect your brand and give a professional user experience.',
                    ],
                ],
            ],
            [
                'icon' => 'icons/domain.svg',
                'order' => 2,
                'translations' => [
                    'ar' => [
                        'title' => 'حجز اسم نطاق (دومين)',
                        'description' => 'احجز اسم موقعك بسهولة واختر من بين مجموعة واسعة من الامتدادات العالمية.',
                    ],
                    'en' => [
                        'title' => 'Domain Name Registration',
                        'description' => 'Easily register your domain name and choose from a wide variety of global extensions.',
                    ],
                ],
            ],
            [
                'icon' => 'icons/wordpress.svg',
                'order' => 3,
                'translations' => [
                    'ar' => [
                        'title' => 'استضافة ووردبريس',
                        'description' => 'تمتع بأداء عالٍ وأمان كامل لموقعك على ووردبريس مع دعم فني دائم.',
                    ],
                    'en' => [
                        'title' => 'WordPress Hosting',
                        'description' => 'High-performance and secure hosting for your WordPress site with full-time support.',
                    ],
                ],
            ],
            [
                'icon' => 'icons/shared-hosting.svg',
                'order' => 4,
                'translations' => [
                    'ar' => [
                        'title' => 'الاستضافة المشتركة',
                        'description' => 'استضافة قوية واقتصادية لموقعك، مع شهادة SSL مجانية وسرعة تشغيل عالية.',
                    ],
                    'en' => [
                        'title' => 'Shared Hosting',
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

