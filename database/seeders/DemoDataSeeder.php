<?php

namespace Database\Seeders;

use App\Enums\KbStatus;
use App\Enums\MessageAuthor;
use App\Enums\TicketChannel;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\BusinessHour;
use App\Models\CannedReply;
use App\Models\KbArticle;
use App\Models\KbCategory;
use App\Models\SlaPolicy;
use App\Models\Tag;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use App\Services\SlaService;
use App\Services\TicketService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class DemoDataSeeder extends Seeder
{
    /**
     * Clearly labelled demo data for the fictional "FiberNet ISP" support
     * centre. All names, addresses and contact details are fictional.
     */
    public function run(): void
    {
        Mail::fake();

        $admin = $this->user('admin@demo.helpdesk.test', 'منى عبد الرحمن', 'admin', 'مديرة الدعم', 'ar', true);
        $supervisor = $this->user('supervisor@demo.helpdesk.test', 'أحمد سامي', 'supervisor', 'مشرف فريق الدعم', 'ar', true);
        $yasmine = $this->user('agent@demo.helpdesk.test', 'ياسمين فؤاد', 'agent', 'أخصائية دعم', 'ar', true);
        $heba = $this->user('heba@demo.helpdesk.test', 'هبة مصطفى', 'agent', 'أخصائية دعم', 'ar', true);
        $kareem = $this->user('kareem@demo.helpdesk.test', 'كريم منصور', 'agent', 'دعم فني', 'ar', true);
        $this->user('salma@demo.helpdesk.test', 'سلمى حسن', 'agent', 'أخصائية دعم (غير نشطة)', 'ar', false);

        $categories = $this->categories();
        $this->sla();

        $tags = $this->tags();
        $this->cannedReplies($admin);
        $this->knowledgeBase($supervisor);

        if (Ticket::query()->exists()) {
            return;
        }

        $this->tickets(
            [$supervisor, $yasmine, $heba, $kareem],
            $categories,
            $tags,
        );
    }

    protected function user(string $email, string $name, string $role, string $jobTitle, string $locale, bool $active): User
    {
        return User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'role' => $role,
                'job_title' => $jobTitle,
                'locale' => $locale,
                'password' => Hash::make('Demo1234!'),
                'is_active' => $active,
                'is_demo' => true,
                'email_verified_at' => now(),
            ],
        );
    }

    /**
     * @return array<string, TicketCategory>
     */
    protected function categories(): array
    {
        $rows = [
            ['outage', 'انقطاع الإنترنت', 'Internet outage', 'انقطاع أو توقف الخدمة تمامًا.', 'Service is down or keeps dropping.', 1],
            ['speed', 'بطء السرعة', 'Slow speed', 'سرعة أقل من الباقة المتفق عليها.', 'Speed is below the subscribed plan.', 2],
            ['billing', 'الفواتير والمدفوعات', 'Billing & payments', 'الفواتير والإيصالات والمدفوعات.', 'Invoices, receipts and payments.', 3],
            ['plan', 'تغيير الباقة', 'Change plan', 'ترقية أو تخفيض الباقة أو ترحيلها.', 'Upgrade, downgrade or move a plan.', 4],
            ['router', 'الراوتر والأجهزة', 'Router & devices', 'إعداد الراوتر والواي فاي والأجهزة.', 'Router setup, Wi-Fi and devices.', 5],
            ['activation', 'تفعيل وتركيب', 'Activation & installation', 'تركيب جديد أو نقل الخدمة.', 'New installation or moving service.', 6],
        ];

        $categories = [];

        foreach ($rows as [$slug, $ar, $en, $descAr, $descEn, $order]) {
            $categories[$slug] = TicketCategory::updateOrCreate(
                ['slug' => $slug],
                [
                    'name_ar' => $ar,
                    'name_en' => $en,
                    'description_ar' => $descAr,
                    'description_en' => $descEn,
                    'is_active' => true,
                    'sort_order' => $order,
                ],
            );
        }

        return $categories;
    }

    protected function sla(): void
    {
        $policies = [
            ['urgent', 60, 480],
            ['high', 240, 1440],
            ['normal', 480, 2880],
            ['low', 1440, 4320],
        ];

        foreach ($policies as [$priority, $first, $resolution]) {
            SlaPolicy::updateOrCreate(
                ['priority' => $priority],
                [
                    'first_response_minutes' => $first,
                    'resolution_minutes' => $resolution,
                    'use_business_hours' => true,
                    'is_active' => true,
                ],
            );
        }

        // Egypt: Sunday to Thursday, 09:00–17:00.
        $workingDays = [0, 1, 2, 3, 4];

        foreach (range(0, 6) as $weekday) {
            BusinessHour::updateOrCreate(
                ['weekday' => $weekday],
                [
                    'opens_at' => '09:00',
                    'closes_at' => '17:00',
                    'is_enabled' => in_array($weekday, $workingDays, true),
                ],
            );
        }
    }

    /**
     * @return array<string, Tag>
     */
    protected function tags(): array
    {
        $rows = [
            ['outage', 'انقطاع', 'Outage', 'rose'],
            ['slow', 'بطء', 'Slow', 'amber'],
            ['router', 'راوتر', 'Router', 'indigo'],
            ['billing', 'فواتير', 'Billing', 'teal'],
            ['upgrade', 'ترقية', 'Upgrade', 'indigo'],
            ['coverage', 'تغطية', 'Coverage', 'slate'],
            ['vip', 'عميل مميز', 'VIP', 'indigo'],
        ];

        $tags = [];

        foreach ($rows as [$slug, $ar, $en, $color]) {
            $tags[$slug] = Tag::updateOrCreate(
                ['slug' => $slug],
                ['name_ar' => $ar, 'name_en' => $en, 'color' => $color],
            );
        }

        return $tags;
    }

    protected function cannedReplies(User $admin): void
    {
        $rows = [
            [
                'استلام الطلب', 'Request received',
                "مرحبًا،\n\nشكرًا لتواصلك مع دعم شبكة فيبر نت. استلمنا طلبك وبدأنا المراجعة. سنوافيك بالتحديث هنا.\n\nمع التحية،\nفريق الدعم",
                "Hello,\n\nThank you for contacting FiberNet support. We have received your request and started reviewing it. We will update you here.\n\nBest regards,\nSupport team",
            ],
            [
                'طلب معلومات إضافية', 'Request more information',
                "مرحبًا،\n\nلنتمكن من متابعة المشكلة بدقة، هل يمكنك إرسال اسم المستخدم أو رقم العقد، وصورة لضوء الراوتر الحالي؟\n\nشكرًا لك.",
                "Hello,\n\nSo we can investigate accurately, could you share your username or contract number and a photo of the router lights?\n\nThank you.",
            ],
            [
                'خطوات إعادة تشغيل الراوتر', 'Router restart steps',
                "مرحبًا،\n\nجرّب فصل الراوتر من الكهرباء لمدة 30 ثانية ثم إعادة توصيله وانتظار دقيقتين. إن استمرت المشكلة أخبرنا بما يظهر على لمبات الراوتر.\n\nشكرًا لتفهمك.",
                "Hello,\n\nPlease unplug the router for 30 seconds, plug it back in and wait two minutes. If the issue remains, tell us what the router lights show.\n\nThank you for your patience.",
            ],
            [
                'تم الحل', 'Resolved',
                "مرحبًا،\n\nسعدنا بحل المشكلة. نغلق التذكرة الآن، وإن عادت يمكنك الرد هنا وستُعاد فتحها تلقائيًا.\n\nيومًا سعيدًا.",
                "Hello,\n\nWe are glad the issue is resolved. We are closing the ticket now; if it returns, reply here and it will reopen automatically.\n\nHave a good day.",
            ],
            [
                'تصعيد لفريق الميدان', 'Escalated to field team',
                "مرحبًا،\n\nصعّدنا حالتك إلى فريق الميدان لفحص الخط في العنوان. سيتم التواصل معك لتحديد موعد الزيارة خلال يوم عمل.\n\nشكرًا لتفهمك.",
                "Hello,\n\nWe escalated your case to the field team to check the line at your address. They will contact you to arrange a visit within one business day.\n\nThank you for your patience.",
            ],
        ];

        foreach ($rows as [$titleAr, $titleEn, $bodyAr, $bodyEn]) {
            CannedReply::updateOrCreate(
                ['title_en' => $titleEn],
                [
                    'title_ar' => $titleAr,
                    'body_ar' => $bodyAr,
                    'body_en' => $bodyEn,
                    'is_active' => true,
                    'created_by' => $admin->id,
                ],
            );
        }
    }

    protected function knowledgeBase(User $author): void
    {
        $categories = [];

        foreach ([
            ['quick-help', 'الدعم السريع', 'Quick help', 'أول خطوات حل المشكلات الشائعة.', 'First steps for common problems.', 1],
            ['speed-router', 'السرعة والراوتر', 'Speed & router', 'ضبط الراوتر وتحسين سرعة الإنترنت.', 'Router setup and improving speed.', 2],
            ['billing-plans', 'الفواتير والباقات', 'Billing & plans', 'كل ما يتعلق بالفاتورة والباقة.', 'Everything about invoices and plans.', 3],
            ['security', 'الأمان', 'Security', 'حماية شبكة المنزل وحسابك.', 'Protecting your home network and account.', 4],
        ] as [$slug, $ar, $en, $descAr, $descEn, $order]) {
            $categories[$slug] = KbCategory::updateOrCreate(
                ['slug' => $slug],
                [
                    'name_ar' => $ar,
                    'name_en' => $en,
                    'description_ar' => $descAr,
                    'description_en' => $descEn,
                    'sort_order' => $order,
                    'is_active' => true,
                ],
            );
        }

        $articles = [
            [
                'group' => 'check-speed',
                'category' => 'speed-router',
                'ar' => [
                    'title' => 'كيف تفحص سرعة الإنترنت بشكل صحيح',
                    'slug' => 'check-internet-speed-ar',
                    'excerpt' => 'خطوات بسيطة تعطيك قراءة دقيقة لسرعة الخط.',
                    'body' => "قبل أن تحكم على السرعة، افصل الأجهزة الأخرى عن الواي فاي واقترب من الراوتر.\n\nاستخدم كابل شبكة إن أمكن، ثم افتح موقع قياس سرعة موثوق من متصفحك. انتظر حتى تنتهي القراءة دون تشغيل فيديو أو تحميل ملفات.\n\nكرّر القياس ثلاث مرات في أوقات مختلفة. إذا كانت النتيجة أقل من 70% من سرعة باقتك بشكل متكرر، أرسل لنا الثلاث نتائج مع اسم المستخدم وسنراجع الخط.\n\nتذكّر أن سرعة الواي فاي تقل مع المسافة والجدران، بينما الكابل يعطي القراءة الأقرب لسرعة الباقة.",
                ],
                'en' => [
                    'title' => 'How to test your internet speed correctly',
                    'slug' => 'check-internet-speed',
                    'excerpt' => 'Simple steps for an accurate reading of your line.',
                    'body' => "Before judging the speed, disconnect other devices from Wi-Fi and move closer to the router.\n\nUse an Ethernet cable if possible, then open a reliable speed-test site in your browser. Wait for the result without streaming video or downloading files.\n\nRepeat the test three times at different times. If the result is repeatedly below 70% of your plan speed, send us the three results with your username and we will check the line.\n\nRemember that Wi-Fi speed drops with distance and walls, while a cable gives the closest reading to your plan.",
                ],
            ],
            [
                'group' => 'service-down',
                'category' => 'quick-help',
                'ar' => [
                    'title' => 'ماذا تفعل عند انقطاع الخدمة',
                    'slug' => 'service-down-ar',
                    'excerpt' => 'افحص الراوتر والخط قبل التواصل مع الدعم.',
                    'body' => "إذا توقفت الخدمة فجأة، ابدأ بفصل الراوتر عن الكهرباء لمدة 30 ثانية ثم أعد توصيله وانتظر دقيقتين.\n\nلاحظ لمبات الراوتر: إذا كان ضوء الإنترنت مطفأ أو أحمر فغالبًا المشكلة في الخط أو المنطقة. إذا كانت اللمبات طبيعية لكن لا يوجد اتصال، جرّب جهازًا آخر.\n\nإن استمر الانقطاع، أرسل لنا اسم المستخدم والعنوان وصورة اللمبات. سنفحص المنطقة ونبلغك بالسبب والوقت المتوقع للإصلاح.\n\nلا تفصل الراوتر أو تعيد ضبطه أكثر من مرة دون داعٍ.",
                ],
                'en' => [
                    'title' => 'What to do when your service is down',
                    'slug' => 'service-down',
                    'excerpt' => 'Check the router and line before contacting support.',
                    'body' => "If the service suddenly stops, first unplug the router for 30 seconds, plug it back in and wait two minutes.\n\nCheck the router lights: if the internet light is off or red, the problem is usually the line or the area. If the lights look normal but there is no connection, try another device.\n\nIf the outage continues, send us your username, address and a photo of the lights. We will check the area and tell you the cause and expected repair time.\n\nDo not unplug or reset the router repeatedly without reason.",
                ],
            ],
            [
                'group' => 'restart-router',
                'category' => 'quick-help',
                'ar' => [
                    'title' => 'إعادة تشغيل الراوتر خطوة بخطوة',
                    'slug' => 'restart-router-ar',
                    'excerpt' => 'الطريقة الصحيحة لإعادة التشغيل دون فقدان الإعدادات.',
                    'body' => "إعادة التشغيل تحل كثيرًا من المشكلات المؤقتة. افصل كابل الكهرباء من الراوتر، وليس زر التشغيل فقط، وانتظر 30 ثانية.\n\nأعد توصيل الكهرباء وانتظر حتى تستقر لمبات الطاقة والإنترنت. قد يستغرق الاتصال حتى دقيقتين.\n\nلا تضغط زر إعادة الضبط (Reset) إلا إذا طلب منك فريق الدعم ذلك، لأنه يمسح إعدادات الشبكة.\n\nإذا لم يعد الاتصال بعد إعادة التشغيل، أرسل لنا صورة اللمبات واسم المستخدم.",
                ],
                'en' => [
                    'title' => 'Restarting your router step by step',
                    'slug' => 'restart-router',
                    'excerpt' => 'The correct way to restart without losing settings.',
                    'body' => "A restart fixes many temporary problems. Unplug the power cable from the router itself (not just press the power button) and wait 30 seconds.\n\nPlug it back in and wait for the power and internet lights to settle. The connection may take up to two minutes.\n\nDo not press the Reset button unless our support team asks you to, because it erases the network settings.\n\nIf the connection does not return after the restart, send us a photo of the lights and your username.",
                ],
            ],
            [
                'group' => 'wifi-password',
                'category' => 'security',
                'ar' => [
                    'title' => 'تغيير اسم وكلمة مرور الواي فاي',
                    'slug' => 'wifi-password-ar',
                    'excerpt' => 'اجعل شبكتك خاصة وآمنة في دقائق.',
                    'body' => "افتح صفحة إعدادات الراوتر من المتصفح باستخدام العنوان المطبوع على ظهر الجهاز، ثم سجّل الدخول ببيانات المسؤول.\n\nابحث عن قسم الشبكة اللاسلكية (Wireless) وغيّر اسم الشبكة وكلمة المرور إلى كلمة قوية لا تقل عن 12 حرفًا وتشمل حروفًا وأرقامًا.\n\nاختر تشفير WPA2 أو WPA3 إن كان متاحًا، ثم احفظ الإعدادات وأعد توصيل أجهزتك.\n\nلا تشارك كلمة المرور على وسائل التواصل، وغيّرها إذا لاحظت أجهزة غريبة متصلة.",
                ],
                'en' => [
                    'title' => 'Change your Wi-Fi name and password',
                    'slug' => 'wifi-password',
                    'excerpt' => 'Make your network private and secure in minutes.',
                    'body' => "Open the router settings page in your browser using the address printed on the device, then sign in with the admin details.\n\nFind the Wireless section and change the network name and password to a strong passphrase of at least 12 characters with letters and numbers.\n\nChoose WPA2 or WPA3 encryption if available, save the settings and reconnect your devices.\n\nDo not share the password on social media, and change it if you notice unknown devices connected.",
                ],
            ],
            [
                'group' => 'read-invoice',
                'category' => 'billing-plans',
                'ar' => [
                    'title' => 'فهم فاتورتك الشهرية',
                    'slug' => 'read-invoice-ar',
                    'excerpt' => 'أين تجد الاشتراك والرسوم والإضافات.',
                    'body' => "تتكوّن فاتورتك من الاشتراك الشهري للباقة، وأي خدمات إضافية طلبتها، ورسوم لمرة واحدة إن وجدت.\n\nراجع تاريخ الفترة ومبلغ الاشتراك ومجموع الإضافات. إذا ظهر خصم غير متوقع، تحقق من ترقية أو خدمة إضافية تم تفعيلها.\n\nيمكنك طلب نسخة PDF من الفاتورة من صفحة الفواتير أو عبر تذكرة دعم. سنرسلها على البريد المسجل خلال يوم عمل.\n\nإن لاحظت خصمًا مكررًا، أرسل لنا رقم الفاتورة وتاريخ العملية وسنراجعها فورًا.",
                ],
                'en' => [
                    'title' => 'Understanding your monthly invoice',
                    'slug' => 'read-invoice',
                    'excerpt' => 'Where to find the plan, fees and add-ons.',
                    'body' => "Your invoice is made up of the monthly plan, any add-on services you requested, and one-time fees if applicable.\n\nCheck the period, the plan amount and the total add-ons. If you see an unexpected charge, look for an upgrade or add-on that was activated.\n\nYou can request a PDF copy from the billing page or through a support ticket. We will email it to the registered address within one business day.\n\nIf you notice a duplicate charge, send us the invoice number and transaction date and we will review it immediately.",
                ],
            ],
            [
                'group' => 'upgrade-plan',
                'category' => 'billing-plans',
                'ar' => [
                    'title' => 'كيف تطلب ترقية الباقة',
                    'slug' => 'upgrade-plan-ar',
                    'excerpt' => 'الخطوات ومتى تبدأ الباقة الجديدة.',
                    'body' => "تتيح لك صفحة الباقات مقارنة السرعات والأسعار وطلب الترقية مباشرة.\n\nبعد تأكيد الطلب، تُفعّل الباقة الجديدة عادة خلال ساعة إذا كان الخط يدعم السرعة. قد تحتاج إعادة تشغيل الراوتر لتطبيق الإعدادات.\n\nتُحسب الفروقات المالية من تاريخ التفعيل، وتظهر في الفاتورة التالية.\n\nإذا لم تتغير السرعة بعد الترقية، أرسل لنا نتيجة قياس جديدة وسنراجع إعدادات الخط.",
                ],
                'en' => [
                    'title' => 'How to request a plan upgrade',
                    'slug' => 'upgrade-plan',
                    'excerpt' => 'The steps and when the new plan starts.',
                    'body' => "The plans page lets you compare speeds and prices and request an upgrade directly.\n\nAfter confirmation, the new plan is usually active within an hour if the line supports the speed. You may need to restart the router to apply the settings.\n\nThe price difference is calculated from the activation date and appears on your next invoice.\n\nIf the speed does not change after the upgrade, send us a new test result and we will check the line settings.",
                ],
            ],
            [
                'group' => 'secure-home-network',
                'category' => 'security',
                'ar' => [
                    'title' => 'حماية شبكة المنزل',
                    'slug' => 'secure-home-network-ar',
                    'excerpt' => 'خطوات عملية لمنع الاستخدام غير المصرح به.',
                    'body' => "ابدأ بتغيير كلمة مرور المسؤول الخاصة بالراوتر وكلمة مرور الواي فاي، ولا تستخدم بيانات سهلة التخمين.\n\nأوقف خاصية WPS إن كانت مفعّلة، فهي تسهّل الاتصال دون كلمة مرور. راجع قائمة الأجهزة المتصلة بانتظام واحظر أي جهاز لا تعرفه.\n\nحدّث برنامج الراوتر إن توفر تحديث من الشركة المصنعة، وافصل الشبكة الضيفية عن شبكة العمل إن كنت تستخدمها.\n\nإذا شككت في اختراق الشبكة، غيّر كلمات المرور فورًا وتواصل مع الدعم لمراجعة السجلات.",
                ],
                'en' => [
                    'title' => 'Protecting your home network',
                    'slug' => 'secure-home-network',
                    'excerpt' => 'Practical steps to prevent unauthorised use.',
                    'body' => "Start by changing the router admin password and the Wi-Fi password, and avoid easily guessed details.\n\nDisable WPS if it is enabled, as it allows connections without a password. Review the connected-devices list regularly and block any device you do not recognise.\n\nUpdate the router firmware if the manufacturer provides an update, and keep a guest network separate from your work network if you use one.\n\nIf you suspect the network was compromised, change the passwords immediately and contact support to review the logs.",
                ],
            ],
            [
                'group' => 'new-installation',
                'category' => 'quick-help',
                'ar' => [
                    'title' => 'ماذا يحدث في زيارة التركيب',
                    'slug' => 'new-installation-ar',
                    'excerpt' => 'استعد لزيارة فريق التركيب.',
                    'body' => "قبل الزيارة، تأكد من توفر مصدر كهرباء قريب من مكان الراوتر ومن إمكانية توصيل الكابل داخل الوحدة.\n\nسيقوم الفني بفحص الخط وتركيب الراوتر وضبط الشبكة، ثم يشرح لك كلمة المرور ويجري اختبار سرعة أمامك.\n\nقد تستغرق الزيارة من 30 إلى 60 دقيقة حسب التوصيلات. إن احتجت تأجيل الموعد، أخبرنا قبل 24 ساعة على الأقل.\n\nاحتفظ برقم العقد وكلمة مرور الواي فاي في مكان آمن.",
                ],
                'en' => [
                    'title' => 'What happens during installation',
                    'slug' => 'new-installation',
                    'excerpt' => 'Get ready for the installation visit.',
                    'body' => "Before the visit, make sure there is a power source near the router location and a way to route the cable inside the unit.\n\nThe technician will check the line, install the router and set up the network, then explain the password and run a speed test in front of you.\n\nThe visit usually takes 30 to 60 minutes depending on the wiring. If you need to reschedule, tell us at least 24 hours in advance.\n\nKeep your contract number and Wi-Fi password in a safe place.",
                ],
            ],
        ];

        foreach ($articles as $definition) {
            foreach (['ar', 'en'] as $locale) {
                $content = $definition[$locale];

                KbArticle::updateOrCreate(
                    ['slug' => $content['slug']],
                    [
                        'kb_category_id' => $categories[$definition['category']]->id,
                        'author_id' => $author->id,
                        'translation_key' => $definition['group'],
                        'locale' => $locale,
                        'title' => $content['title'],
                        'excerpt' => $content['excerpt'],
                        'body' => $content['body'],
                        'status' => KbStatus::Published,
                        'published_at' => now()->subDays(6),
                        'views' => random_int(24, 260),
                    ],
                );
            }
        }

        KbArticle::updateOrCreate(
            ['slug' => 'upcoming-plans-ar'],
            [
                'kb_category_id' => $categories['billing-plans']->id,
                'author_id' => $author->id,
                'translation_key' => 'upcoming-plans',
                'locale' => 'ar',
                'title' => 'الباقات القادمة في خارطة الطريق',
                'excerpt' => 'مسودة داخلية لا تظهر في البوابة العامة.',
                'body' => "هذا مقال تجريبي بحالة مسودة. لا يظهر للزوار ولا يظهر في البحث العام.\n\nيمكن للمشرف نشر المقال عندما يكتمل محتواه.",
                'status' => KbStatus::Draft,
                'published_at' => null,
                'views' => 0,
            ],
        );
    }

    /**
     * @param  array<int, User>  $agents
     * @param  array<string, TicketCategory>  $categories
     * @param  array<string, Tag>  $tags
     */
    protected function tickets(array $agents, array $categories, array $tags): void
    {
        $service = app(TicketService::class);
        $sla = app(SlaService::class);

        $arabicPeople = [
            ['أحمد مصطفى', 'ahmed.mostafa@example.com'],
            ['منى سعيد', 'mona.saeed@example.com'],
            ['محمود عبد الله', 'mahmoud.abdullah@example.com'],
            ['هبة حسن', 'heba.hassan@example.com'],
            ['كريم فؤاد', 'karim.fouad@example.com'],
            ['سارة إبراهيم', 'sara.ibrahim@example.com'],
            ['ياسمين علي', 'yasmin.ali@example.com'],
            ['عمر خالد', 'omar.khaled@example.com'],
            ['ندى فتحي', 'nada.fathy@example.com'],
            ['طارق الشريف', 'tarek.sherif@example.com'],
            ['ريم سامي', 'reem.samy@example.com'],
            ['خالد منصور', 'khaled.mansour@example.com'],
            ['دينا عادل', 'dina.adel@example.com'],
            ['مصطفى كامل', 'mostafa.kamel@example.com'],
            ['أمل رشدي', 'amal.rushdy@example.com'],
            ['حسام الدين', 'hossam.eldin@example.com'],
            ['نور الهدى', 'nour.elhoda@example.com'],
            ['شريف عاطف', 'sherif.atef@example.com'],
            ['مي عبد العزيز', 'mai.aziz@example.com'],
            ['وليد صلاح', 'walid.salah@example.com'],
        ];

        $englishPeople = [
            ['Mariam Adel', 'mariam.adel@example.com'],
            ['Omar Nabil', 'omar.nabil@example.com'],
            ['Dina Fathy', 'dina.fathy@example.com'],
            ['Youssef Ashraf', 'youssef.ashraf@example.com'],
            ['Salma Hany', 'salma.hany@example.com'],
            ['Karim Sherif', 'karim.sherif@example.com'],
        ];

        $districts = ['مدينة نصر', 'المعادي', 'الشيخ زايد', 'القاهرة الجديدة', 'المنصورة', 'الإسكندرية', 'مدينة السادات'];

        $arabicTemplates = [
            ['subject' => 'انقطاع كامل للخدمة منذ الصباح', 'category' => 'outage', 'priority' => TicketPriority::Urgent, 'tags' => ['outage'],
                'opening' => 'استيقظت اليوم ولا يوجد إنترنت نهائيًا في الشقة. لمبة الراوتر الحمراء مطفأة تمامًا.',
                'reply' => 'أهلًا :name، شكرًا لإبلاغنا. نراجع منطقة :district الآن وسنوافيك بالسبب والوقت المتوقع للإصلاح.',
                'follow' => 'ما زالت الخدمة متوقفة حتى الآن.'],
            ['subject' => 'السرعة أقل من الباقة مساءً', 'category' => 'speed', 'priority' => TicketPriority::High, 'tags' => ['slow'],
                'opening' => 'الباقة 100 ميجا لكن السرعة بعد الساعة السابعة مساءً لا تتجاوز 12 ميجا رغم أن الراوتر قريب.',
                'reply' => 'أهلًا :name، هل يمكنك إرسال نتيجة قياس بالكابل ونتيجتين على الواي فاي حتى نحدد إن كانت المشكلة في الخط أم الشبكة؟'],
            ['subject' => 'الواي فاي لا يظهر على الهاتف', 'category' => 'router', 'priority' => TicketPriority::Normal, 'tags' => ['router'],
                'opening' => 'شبكة الواي فاي اختفت من قائمة الهاتف واللابتوب منذ أمس، مع أن لمبات الراوتر تعمل.',
                'reply' => 'أهلًا :name، جرّب إعادة تشغيل الراوتر لمدة 30 ثانية وأخبرنا باسم الشبكة الذي يظهر بعده.'],
            ['subject' => 'خصم مكرر في فاتورة الشهر', 'category' => 'billing', 'priority' => TicketPriority::High, 'tags' => ['billing', 'vip'],
                'opening' => 'لاحظت خصمين متتاليين بقيمة الاشتراك الشهري على البطاقة. أرجو مراجعة العملية وإلغاء أحد الخصمين.',
                'reply' => 'أهلًا :name، راجعنا الفاتورة وسنعيد المبلغ الزائد خلال 3 أيام عمل. أرسلنا لك إيصال المراجعة على البريد.'],
            ['subject' => 'أرغب في ترقية الباقة إلى 100 ميجا', 'category' => 'plan', 'priority' => TicketPriority::Low, 'tags' => ['upgrade'],
                'opening' => 'أستخدم باقة 40 ميجا وأحتاج سرعة أعلى للعمل من المنزل. ما متطلبات الترقية؟',
                'reply' => 'أهلًا :name، خطك يدعم 100 ميجا. يمكننا تفعيل الترقية اليوم وسيظهر فرق السعر في الفاتورة التالية.'],
            ['subject' => 'انقطاع متكرر كل ساعة', 'category' => 'outage', 'priority' => TicketPriority::Urgent, 'tags' => ['outage'],
                'opening' => 'الخدمة تعمل دقائق ثم تنقطع، وتتكرر المشكلة كل ساعة تقريبًا منذ يومين.',
                'reply' => 'أهلًا :name، نلاحظ إعادة مزامنة على الخط. صعّدنا الحالة لفريق الميدان لفحص الكابل.'],
            ['subject' => 'البث يتقطع أثناء مكالمات العمل', 'category' => 'speed', 'priority' => TicketPriority::High, 'tags' => ['slow'],
                'opening' => 'أعمالي تعتمد على مكالمات الفيديو، لكن الصوت يتقطع والاتصال ينهار عدة مرات يوميًا.',
                'reply' => 'أهلًا :name، سنفحص جودة الخط والراوتر. هل تستخدم الواي فاي أم كابل الشبكة؟'],
            ['subject' => 'نسيت كلمة مرور إعدادات الراوتر', 'category' => 'router', 'priority' => TicketPriority::Normal, 'tags' => ['router'],
                'opening' => 'أحتاج تغيير اسم الشبكة لكن نسيت كلمة مرور الدخول لإعدادات الراوتر.',
                'reply' => 'أهلًا :name، جرّب بيانات الدخول المطبوعة على ظهر الراوتر. إن لم تعمل سنرشدك لإعادة الضبط الآمن.'],
            ['subject' => 'أحتاج إيصال الدفع للاشتراك السنوي', 'category' => 'billing', 'priority' => TicketPriority::Normal, 'tags' => ['billing'],
                'opening' => 'أحتاج إيصال الدفع السنوي باسم الشركة للصرف المحاسبي.',
                'reply' => 'أهلًا :name، أرسلنا الإيصال بصيغة PDF على البريد المسجل. أخبرنا إن احتجت اسمًا أو رقم ضريبيًا مختلفًا.'],
            ['subject' => 'تخفيض الباقة بعد الانتقال', 'category' => 'plan', 'priority' => TicketPriority::Normal, 'tags' => ['upgrade'],
                'opening' => 'انتقلت إلى شقة أصغر وأريد تخفيض الباقة من 100 إلى 40 ميجا.',
                'reply' => 'أهلًا :name، يمكننا التخفيض بداية الشهر القادم لتفادي فروق غير مستحقة.'],
            ['subject' => 'التركيب الجديد متأخر عن الموعد', 'category' => 'activation', 'priority' => TicketPriority::High, 'tags' => ['coverage'],
                'opening' => 'كان موعد التركيب أمس بين 2 و4 عصرًا ولم يحضر الفني ولم يتصل أحد.',
                'reply' => 'نعتذر عن التأخير :name. أعدنا جدولة الزيارة غدًا صباحًا وأرسلنا رقم طلب التركيب.'],
            ['subject' => 'الخدمة تعمل ثم تنقطع بعد دقائق', 'category' => 'outage', 'priority' => TicketPriority::High, 'tags' => ['outage'],
                'opening' => 'أتصل بالإنترنت لدقائق ثم تنقطع الإشارة ويعود الضوء الأحمر.',
                'reply' => 'أهلًا :name، يبدو أن هناك ضعفًا في الإشارة. سنراجع مستوى الخط ونوافيك.'],
            ['subject' => 'سرعة الرفع منخفضة للعمل عن بُعد', 'category' => 'speed', 'priority' => TicketPriority::Normal, 'tags' => ['slow'],
                'opening' => 'أرفع ملفات كبيرة يوميًا وسرعة الرفع أقل بكثير من المذكور في الباقة.',
                'reply' => 'أهلًا :name، أرسل لنا نتيجة قياس تُظهر سرعة الرفع وسنقارنها بإعدادات الخط.'],
            ['subject' => 'كيف أوصل الراوتر بالتلفاز', 'category' => 'router', 'priority' => TicketPriority::Low, 'tags' => ['router'],
                'opening' => 'أريد توصيل التلفاز بالإنترنت عبر كابل من الراوتر. أي منفذ أستخدم؟',
                'reply' => 'أهلًا :name، استخدم أي منفذ LAN أصفر في الراوتر وكابل شبكة عادي، ثم اختر الشبكة السلكية من التلفاز.'],
            ['subject' => 'فاتورة باسم غير صحيح', 'category' => 'billing', 'priority' => TicketPriority::High, 'tags' => ['billing'],
                'opening' => 'الفاتورة صدرت باسم قديم وأحتاج تصحيحها إلى اسم الشركة الحالي.',
                'reply' => 'أهلًا :name، صحّحنا بيانات الفاتورة وأعدنا إصدارها. ستجد النسخة الجديدة على بريدك.'],
            ['subject' => 'هل يمكن ترحيل الباقة عند الانتقال؟', 'category' => 'plan', 'priority' => TicketPriority::Normal, 'tags' => ['upgrade', 'coverage'],
                'opening' => 'سأنتقل إلى مدينة أخرى الشهر القادم. هل يمكن نقل نفس الباقة دون تغيير الرقم؟',
                'reply' => 'أهلًا :name، يمكن نقل الخدمة إذا كان العنوان الجديد داخل التغطية. أرسل لنا العنوان لنؤكد.'],
            ['subject' => 'انقطاع في مدينة نصر منذ ساعتين', 'category' => 'outage', 'priority' => TicketPriority::Urgent, 'tags' => ['outage'],
                'opening' => 'الإنترنت مقطوع في العمارة كلها منذ ساعتين تقريبًا.',
                'reply' => 'أهلًا :name، هناك صيانة طارئة في المنطقة. الوقت المتوقع لعودة الخدمة خلال ساعة.'],
            ['subject' => 'تأخر تحميل الصفحات والألعاب', 'category' => 'speed', 'priority' => TicketPriority::High, 'tags' => ['slow'],
                'opening' => 'الصفحات تتأخر في الفتح والألعاب تعطي زمن استجابة مرتفع جدًا.',
                'reply' => 'أهلًا :name، قد يكون هناك استخدام كثيف على الشبكة. سنفحص جودة الاتصال وننصح بإعدادات مناسبة للألعاب.'],
            ['subject' => 'إعادة ضبط الراوتر مسحت الإعدادات', 'category' => 'router', 'priority' => TicketPriority::Normal, 'tags' => ['router'],
                'opening' => 'ضغطت زر إعادة الضبط بالخطأ وفقدت اسم الشبكة وكلمة المرور.',
                'reply' => 'أهلًا :name، لا مشكلة. سنرشدك خطوة بخطوة لإعادة ضبط اسم الشبكة وكلمة المرور.'],
            ['subject' => 'استفسار عن رسوم التركيب', 'category' => 'billing', 'priority' => TicketPriority::Low, 'tags' => ['billing'],
                'opening' => 'ما تفاصيل رسوم التركيب وهل تُخصم ضمن العرض الحالي؟',
                'reply' => 'أهلًا :name، رسوم التركيب لمرة واحدة وتُعفى ضمن العرض الحالي. أرسلنا لك تفاصيل العرض.'],
            ['subject' => 'نقل الخدمة إلى عنوان جديد', 'category' => 'activation', 'priority' => TicketPriority::Normal, 'tags' => ['coverage'],
                'opening' => 'أريد نقل الخدمة إلى شقة جديدة في نفس المنطقة الأسبوع القادم.',
                'reply' => 'أهلًا :name، أرسل لنا العنوان الجديد ورقم العقد وسنجدول زيارة نقل الخدمة.'],
            ['subject' => 'لا يوجد اتصال بعد عاصفة أمس', 'category' => 'outage', 'priority' => TicketPriority::Urgent, 'tags' => ['outage'],
                'opening' => 'بعد العاصفة أمس توقف الإنترنت تمامًا ولمبات الراوتر لا تعمل.',
                'reply' => 'أهلًا :name، قد يكون الكابل الخارجي تأثر بالعاصفة. صعّدنا الحالة لفريق الميدان للفحص.'],
            ['subject' => 'السرعة تنخفض عند استخدام أكثر من جهاز', 'category' => 'speed', 'priority' => TicketPriority::Normal, 'tags' => ['slow'],
                'opening' => 'عند تشغيل التلفاز والهاتف معًا تصبح السرعة ضعيفة جدًا.',
                'reply' => 'أهلًا :name، هذا طبيعي جزئيًا مع توزيع السرعة. سنراجع إعدادات الراوتر لتحسين التوزيع.'],
            ['subject' => 'الراوتر يسخن وينطفئ', 'category' => 'router', 'priority' => TicketPriority::High, 'tags' => ['router'],
                'opening' => 'الراوتر يسخن بشدة وينطفئ من تلقاء نفسه بعد ساعات من الاستخدام.',
                'reply' => 'أهلًا :name، ضعه في مكان جيد التهوية ولا تضعه فوق جهاز آخر. إن تكرر الانطفاء سنستبدله.'],
            ['subject' => 'تأخير في تفعيل خط جديد', 'category' => 'activation', 'priority' => TicketPriority::High, 'tags' => ['coverage'],
                'opening' => 'تعاقدت على خط جديد منذ أربعة أيام ولم يتم التفعيل حتى الآن.',
                'reply' => 'أهلًا :name، طلبك في مرحلة التفعيل النهائي. سنتابعه اليوم ونبلغك عند الجاهزية.'],
        ];

        $englishTemplates = [
            ['subject' => 'Internet down since this morning', 'category' => 'outage', 'priority' => TicketPriority::Urgent, 'tags' => ['outage'],
                'opening' => 'There has been no connection at all since 8am. The router internet light is off.',
                'reply' => 'Hi :name, thanks for reporting. We are checking the :district area and will update you with the cause and expected fix.',
                'follow' => 'Still offline, any update?'],
            ['subject' => 'Evening speeds far below the plan', 'category' => 'speed', 'priority' => TicketPriority::High, 'tags' => ['slow'],
                'opening' => 'I am on the 100 Mbps plan but evenings drop to under 15 Mbps on both Wi-Fi and cable.',
                'reply' => 'Hi :name, please send one cable and two Wi-Fi speed results so we can compare the line with the router.'],
            ['subject' => 'Double charge on this month invoice', 'category' => 'billing', 'priority' => TicketPriority::Normal, 'tags' => ['billing'],
                'opening' => 'My card was charged twice for the same monthly plan. Please review and refund one charge.',
                'reply' => 'Hi :name, we confirmed the duplicate and will refund it within three business days.'],
            ['subject' => 'Upgrade to the 100 Mbps plan', 'category' => 'plan', 'priority' => TicketPriority::Low, 'tags' => ['upgrade'],
                'opening' => 'I would like to upgrade from 40 to 100 Mbps for working from home.',
                'reply' => 'Hi :name, your line supports 100 Mbps. We can activate it today and the difference will appear next month.'],
            ['subject' => 'Wi-Fi network not showing', 'category' => 'router', 'priority' => TicketPriority::Normal, 'tags' => ['router'],
                'opening' => 'My Wi-Fi network disappeared from all devices yesterday although the router lights are on.',
                'reply' => 'Hi :name, please restart the router for 30 seconds and tell us the network name that appears.'],
            ['subject' => 'Installation appointment missed', 'category' => 'activation', 'priority' => TicketPriority::High, 'tags' => ['coverage'],
                'opening' => 'The installation was scheduled yesterday between 2 and 4pm but nobody arrived or called.',
                'reply' => 'We apologise for the delay :name. We rescheduled the visit for tomorrow morning.'],
        ];

        $scores = [5, 5, 4, 5, 4, 3, 4, 5, 2, 5, 4, 4];
        $scoreComments = [
            'تمت المساعدة بسرعة ووضوح، شكرًا لكم.',
            'الخدمة جيدة والاتصال مستقر الآن.',
            'الفني شرح المشكلة وحلها في نفس اليوم.',
            'تحسن الاتصال بعد المتابعة، شكرًا.',
            'التواصل كان واضحًا والنتيجة مرضية.',
            'انتظرت قليلًا قبل الرد لكن المشكلة حُلت.',
            'السرعة تحسنت بعد ضبط الراوتر.',
            'الخدمة ممتازة، شكرًا لفريق الدعم.',
            'انقطع الاتصال مرة أخرى بعد يوم، وتواصلت مع الدعم.',
            'كل شيء يعمل بشكل جيد الآن.',
            'المتابعة كانت جيدة والنتيجة مرضية.',
            'الخدمة جيدة بشكل عام.',
        ];

        $totalSolved = 52;
        $totalOpen = 20;
        $englishIndex = 0;

        // Solved/closed history: spread over ~4 months, 44 of them rated.
        for ($i = 0; $i < $totalSolved; $i++) {
            $useEnglish = $i % 12 === 11 && $englishIndex < count($englishTemplates);
            $template = $useEnglish ? $englishTemplates[$englishIndex] : $arabicTemplates[$i % count($arabicTemplates)];

            if ($useEnglish) {
                $englishIndex++;
            }

            $person = $useEnglish ? $englishPeople[$englishIndex % count($englishPeople)] : $arabicPeople[$i % count($arabicPeople)];
            $locale = $useEnglish ? 'en' : 'ar';
            $district = $districts[$i % count($districts)];
            $agent = $agents[$i % count($agents)];

            $daysAgo = $i < 24 ? 30 + ($i * 4) : (52 - $i);
            $createdAt = now()->subDays($daysAgo)->setTime(9 + ($i % 8), ($i * 17) % 60);
            $status = $i % 2 === 0 ? TicketStatus::Solved : TicketStatus::Closed;

            $ticket = $service->create([
                'subject' => $template['subject'],
                'description' => $this->fill($template['opening'], $person[0], $district),
                'requester_name' => $person[0],
                'requester_email' => $person[1],
                'requester_locale' => $locale,
                'category_id' => $categories[$template['category']]->id,
                'priority' => $template['priority'],
                'channel' => TicketChannel::Portal,
            ], [], null, false);

            $ticket->forceFill([
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
                'assigned_to' => $agent->id,
                'last_reply_at' => $createdAt,
            ])->save();

            $sla->apply($ticket, $createdAt);
            $ticket->save();

            $ticket->messages()->first()?->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();

            $replyAt = $createdAt->copy()->addMinutes(20 + ($i % 4) * 10);
            $message = $service->addMessage($ticket, [
                'author_type' => MessageAuthor::Agent,
                'author_name' => $agent->name,
                'author_email' => $agent->email,
                'body' => $this->fill($template['reply'], $person[0], $district),
                'source' => 'agent',
                'user' => $agent,
            ], [], false);
            $message->forceFill(['created_at' => $replyAt, 'updated_at' => $replyAt])->save();

            if ($i % 4 === 0 && isset($template['follow'])) {
                $followAt = $createdAt->copy()->addMinutes(90 + ($i % 3) * 20);
                $follow = $service->addMessage($ticket, [
                    'author_type' => MessageAuthor::Requester,
                    'author_name' => $person[0],
                    'author_email' => $person[1],
                    'body' => $this->fill($template['follow'], $person[0], $district),
                    'source' => 'portal',
                ], [], false);
                $follow->forceFill(['created_at' => $followAt, 'updated_at' => $followAt])->save();
            }

            $resolvedAt = $createdAt->copy()->addMinutes(60 + ($i % 8) * 40);

            $ticket->tags()->sync(collect($template['tags'])->map(fn ($slug) => $tags[$slug]->id)->all());
            $ticket->first_responded_at = $replyAt;
            $ticket->resolved_at = $resolvedAt;
            $ticket->closed_at = $status === TicketStatus::Closed ? $resolvedAt->copy()->addHours(12) : null;
            $ticket->first_response_breached_at = null;
            $ticket->resolution_breached_at = $i % 29 === 0 ? $resolvedAt : null;
            $ticket->last_reply_at = $replyAt;
            $ticket->status = $status;
            $ticket->updated_at = $resolvedAt;
            $ticket->save();

            if ($i < 44) {
                $ticket->csat()->create([
                    'score' => $scores[$i % count($scores)],
                    'comment' => $scoreComments[$i % count($scoreComments)],
                    'submitted_at' => $resolvedAt->copy()->addHours(2),
                ]);
            }
        }

        // Recent active tickets: 14 on track, 4 at risk, 2 breached (~10%).
        for ($i = 0; $i < $totalOpen; $i++) {
            $useEnglish = $i % 12 === 11 && $englishIndex < count($englishTemplates);
            $template = $useEnglish ? $englishTemplates[$englishIndex] : $arabicTemplates[$i % count($arabicTemplates)];

            if ($useEnglish) {
                $englishIndex++;
            }

            $person = $useEnglish ? $englishPeople[$englishIndex % count($englishPeople)] : $arabicPeople[$i % count($arabicPeople)];
            $locale = $useEnglish ? 'en' : 'ar';
            $district = $districts[($i + 3) % count($districts)];
            $status = [TicketStatus::Open, TicketStatus::Open, TicketStatus::Pending, TicketStatus::OnHold][$i % 4];
            $assigned = $i % 5 === 0 ? null : $agents[$i % count($agents)];

            $createdAt = now()->subMinutes(15 + $i * 14)->setSecond(0);

            $ticket = $service->create([
                'subject' => $template['subject'],
                'description' => $this->fill($template['opening'], $person[0], $district),
                'requester_name' => $person[0],
                'requester_email' => $person[1],
                'requester_locale' => $locale,
                'category_id' => $categories[$template['category']]->id,
                'priority' => $template['priority'],
                'channel' => TicketChannel::Portal,
            ], [], null, false);

            $ticket->forceFill([
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
                'assigned_to' => $assigned?->id,
                'last_reply_at' => $createdAt,
            ])->save();

            $sla->apply($ticket, $createdAt);
            $ticket->save();

            $ticket->messages()->first()?->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();

            $firstResponseAt = null;

            // Only on-track tickets have an agent reply; at-risk/breached wait.
            if ($i < 14 && $assigned && $i % 3 !== 0 && isset($template['reply'])) {
                $replyAt = $createdAt->copy()->addMinutes(15 + ($i % 3) * 10);
                $message = $service->addMessage($ticket, [
                    'author_type' => MessageAuthor::Agent,
                    'author_name' => $assigned->name,
                    'author_email' => $assigned->email,
                    'body' => $this->fill($template['reply'], $person[0], $district),
                    'source' => 'agent',
                    'user' => $assigned,
                ], [], false);
                $message->forceFill(['created_at' => $replyAt, 'updated_at' => $replyAt])->save();
                $firstResponseAt = $replyAt;
            }

            if ($i % 7 === 0 && isset($template['follow'])) {
                $followAt = $createdAt->copy()->addMinutes(60 + ($i % 3) * 20);
                $follow = $service->addMessage($ticket, [
                    'author_type' => MessageAuthor::Requester,
                    'author_name' => $person[0],
                    'author_email' => $person[1],
                    'body' => $this->fill($template['follow'], $person[0], $district),
                    'source' => 'portal',
                ], [], false);
                $follow->forceFill(['created_at' => $followAt, 'updated_at' => $followAt])->save();
            }

            $ticket->tags()->sync(collect($template['tags'])->map(fn ($slug) => $tags[$slug]->id)->all());

            // Shape service targets relative to now so the board looks realistic.
            if ($i >= 18) {
                $ticket->first_response_due_at = now()->subHours(2);
                $ticket->first_response_breached_at = now()->subHour();
                $ticket->resolution_due_at = now()->addDay();
                $ticket->first_responded_at = null;
            } elseif ($i >= 14) {
                $ticket->first_response_due_at = now()->addMinutes(30 + ($i - 14) * 20);
                $ticket->first_response_breached_at = null;
                $ticket->resolution_due_at = now()->addDays(1);
                $ticket->first_responded_at = null;
            } else {
                $ticket->first_response_due_at = now()->addHours(3 + ($i % 5));
                $ticket->first_response_breached_at = null;
                $ticket->resolution_due_at = now()->addDays(1 + ($i % 3));
                $ticket->first_responded_at = $firstResponseAt;
            }

            $ticket->resolution_breached_at = null;
            $ticket->last_reply_at = $createdAt;
            $ticket->status = $status;
            $ticket->updated_at = $createdAt;
            $ticket->save();
        }
    }

    protected function fill(string $text, string $name, string $district): string
    {
        return str_replace([':name', ':district'], [$name, $district], $text);
    }
}
