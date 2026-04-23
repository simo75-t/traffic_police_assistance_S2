<?php

namespace Database\Seeders\Support;

class TrafficSeedData
{
    public static function users(): array
    {
        return [
            'admin' => [
                'name' => 'سامي الحداد',
                'email' => 'admin@traffic.local',
                'phone' => '0933000001',
                'role' => 'admin',
                'profile_image' => 'profiles/admin-sami.jpg',
                'is_active' => true,
                'last_seen_minutes_ago' => 5,
            ],
            'manager' => [
                'name' => 'رنا المصري',
                'email' => 'manager@traffic.local',
                'phone' => '0933000002',
                'role' => 'Police_manager',
                'profile_image' => 'profiles/manager-rana.jpg',
                'is_active' => true,
                'last_seen_minutes_ago' => 8,
            ],
            'officers' => [
                ['name' => 'أحمد ناصر', 'email' => 'officer.ahmad@traffic.local', 'phone' => '0933000003', 'role' => 'Police_officer', 'profile_image' => 'profiles/officer-ahmad.jpg', 'is_active' => true, 'last_seen_minutes_ago' => 15],
                ['name' => 'سامر درويش', 'email' => 'officer.samer@traffic.local', 'phone' => '0933000004', 'role' => 'Police_officer', 'profile_image' => 'profiles/officer-samer.jpg', 'is_active' => true, 'last_seen_minutes_ago' => 18],
                ['name' => 'لينا قصاب', 'email' => 'officer.lina@traffic.local', 'phone' => '0933000005', 'role' => 'Police_officer', 'profile_image' => 'profiles/officer-lina.jpg', 'is_active' => true, 'last_seen_minutes_ago' => 21],
                ['name' => 'يوسف العلي', 'email' => 'officer.yousef@traffic.local', 'phone' => '0933000006', 'role' => 'Police_officer', 'profile_image' => 'profiles/officer-yousef.jpg', 'is_active' => true, 'last_seen_minutes_ago' => 11],
                ['name' => 'مها سليمان', 'email' => 'officer.maha@traffic.local', 'phone' => '0933000007', 'role' => 'Police_officer', 'profile_image' => 'profiles/officer-maha.jpg', 'is_active' => true, 'last_seen_minutes_ago' => 14],
                ['name' => 'طارق حامد', 'email' => 'officer.tariq@traffic.local', 'phone' => '0933000008', 'role' => 'Police_officer', 'profile_image' => 'profiles/officer-tariq.jpg', 'is_active' => false, 'last_seen_minutes_ago' => 240],
            ],
        ];
    }

    public static function areas(): array
    {
        return [
            ['name' => 'المالكي', 'city' => 'دمشق', 'center_lat' => 33.5145221, 'center_lng' => 36.2765127],
            ['name' => 'كفرسوسة', 'city' => 'دمشق', 'center_lat' => 33.48461765471776, 'center_lng' => 36.267768120406416],
            ['name' => 'المزة', 'city' => 'دمشق', 'center_lat' => 33.50656003517311, 'center_lng' => 36.242946068581766],
            ['name' => 'أبو رمانة', 'city' => 'دمشق', 'center_lat' => 33.5179031, 'center_lng' => 36.2841402],
            ['name' => 'البرامكة', 'city' => 'دمشق', 'center_lat' => 33.50748850562386, 'center_lng' => 36.28847201435723],
            ['name' => 'الشام الجديدة', 'city' => 'دمشق', 'center_lat' => 33.52938744770851, 'center_lng' => 36.23145780204516],
        ];
    }

    public static function vehicles(): array
    {
        return [
            ['plate_number' => 'دمشق 123456', 'owner_name' => 'فارس محمود', 'model' => 'كيا ريو 2018', 'color' => 'أبيض'],
            ['plate_number' => 'دمشق 654321', 'owner_name' => 'نور حسن', 'model' => 'هيونداي أكسنت 2020', 'color' => 'فضي'],
            ['plate_number' => 'ريف دمشق 778899', 'owner_name' => 'خالد عثمان', 'model' => 'تويوتا كورولا 2017', 'color' => 'أسود'],
            ['plate_number' => 'دمشق 112233', 'owner_name' => 'مايا يوسف', 'model' => 'نيسان صني 2019', 'color' => 'أزرق'],
            ['plate_number' => 'دمشق 223344', 'owner_name' => 'هادي إبراهيم', 'model' => 'تويوتا يارس 2016', 'color' => 'رمادي'],
            ['plate_number' => 'دمشق 334455', 'owner_name' => 'دينا قطوب', 'model' => 'كيا سيراتو 2021', 'color' => 'أحمر'],
            ['plate_number' => 'دمشق 445566', 'owner_name' => 'رامي الزين', 'model' => 'هيونداي إلنترا 2018', 'color' => 'أبيض'],
            ['plate_number' => 'دمشق 556677', 'owner_name' => 'سلمى الحريري', 'model' => 'سوزوكي سويفت 2015', 'color' => 'أصفر'],
            ['plate_number' => 'ريف دمشق 667788', 'owner_name' => 'أنس دباس', 'model' => 'رينو لوجان 2014', 'color' => 'فضي'],
            ['plate_number' => 'دمشق 778811', 'owner_name' => 'لما حاج', 'model' => 'هوندا سيفيك 2022', 'color' => 'أسود'],
            ['plate_number' => 'دمشق 889922', 'owner_name' => 'قيس نابلسي', 'model' => 'بيجو 301 2019', 'color' => 'أزرق'],
            ['plate_number' => 'دمشق 991133', 'owner_name' => 'هبة درويش', 'model' => 'تويوتا كامري 2020', 'color' => 'أبيض'],
            ['plate_number' => 'ريف دمشق 224466', 'owner_name' => 'مازن صباغ', 'model' => 'ميتسوبيشي لانسر 2013', 'color' => 'رمادي'],
            ['plate_number' => 'دمشق 335577', 'owner_name' => 'نسرين علوان', 'model' => 'كيا بيكانتو 2017', 'color' => 'زهري'],
            ['plate_number' => 'دمشق 446688', 'owner_name' => 'وائل حمدان', 'model' => 'هيونداي توسان 2021', 'color' => 'أخضر'],
        ];
    }

    public static function reportLocations(): array
    {
        return [
            ['area_name' => 'المالكي', 'address' => 'قرب دوار المالكي', 'street_name' => 'شارع المالكي', 'landmark' => 'مقابل الصيدلية', 'latitude' => 33.5139550, 'longitude' => 36.2759020, 'city' => 'دمشق'],
            ['area_name' => 'كفرسوسة', 'address' => 'طريق الخدمة في كفرسوسة', 'street_name' => 'الشارع الرئيسي في كفرسوسة', 'landmark' => 'بجانب جسر المشاة', 'latitude' => 33.4858710, 'longitude' => 36.2551420, 'city' => 'دمشق'],
            ['area_name' => 'المزة', 'address' => 'مدخل أوتستراد المزة', 'street_name' => 'أوتستراد المزة', 'landmark' => 'عند علامة مسار الطوارئ 4', 'latitude' => 33.5081140, 'longitude' => 36.2336110, 'city' => 'دمشق'],
            ['area_name' => 'أبو رمانة', 'address' => 'ساحة أبو رمانة', 'street_name' => 'شارع شكري القوتلي', 'landmark' => 'بالقرب من المخبز', 'latitude' => 33.5177520, 'longitude' => 36.2838140, 'city' => 'دمشق'],
            ['area_name' => 'البرامكة', 'address' => 'موقف البرامكة', 'street_name' => 'شارع الثورة', 'landmark' => 'عند بوابة الجامعة', 'latitude' => 33.5086840, 'longitude' => 36.2917810, 'city' => 'دمشق'],
            ['area_name' => 'الشام الجديدة', 'address' => 'الشارع التجاري في الشام الجديدة', 'street_name' => 'شارع الشام الجديدة', 'landmark' => 'بالقرب من فرع المصرف', 'latitude' => 33.5291020, 'longitude' => 36.2319910, 'city' => 'دمشق'],
        ];
    }

    public static function violationLocations(): array
    {
        return [
            ['area_name' => 'المالكي', 'address' => 'موقف جانبي في المالكي', 'street_name' => 'شارع المالكي', 'landmark' => 'بجانب مدخل المصرف', 'latitude' => 33.5141020, 'longitude' => 36.2762010, 'city' => 'دمشق'],
            ['area_name' => 'كفرسوسة', 'address' => 'تقاطع كفرسوسة', 'street_name' => 'الشارع الرئيسي في كفرسوسة', 'landmark' => 'عند الإشارة الضوئية B2', 'latitude' => 33.4864120, 'longitude' => 36.2545010, 'city' => 'دمشق'],
            ['area_name' => 'المزة', 'address' => 'المسرب الغربي في المزة', 'street_name' => 'أوتستراد المزة', 'landmark' => 'ضمن مسار الطوارئ', 'latitude' => 33.5090110, 'longitude' => 36.2328870, 'city' => 'دمشق'],
            ['area_name' => 'أبو رمانة', 'address' => 'موقف أبو رمانة الجانبي', 'street_name' => 'شارع شكري القوتلي', 'landmark' => 'قبل العيادة', 'latitude' => 33.5172110, 'longitude' => 36.2843330, 'city' => 'دمشق'],
            ['area_name' => 'البرامكة', 'address' => 'ممر البرامكة', 'street_name' => 'شارع الثورة', 'landmark' => 'عند مدخل الكلية', 'latitude' => 33.5091140, 'longitude' => 36.2924170, 'city' => 'دمشق'],
            ['area_name' => 'الشام الجديدة', 'address' => 'المسرب التجاري في الشام الجديدة', 'street_name' => 'شارع الشام الجديدة', 'landmark' => 'بالقرب من ممر المشاة الضوئي', 'latitude' => 33.5295500, 'longitude' => 36.2322050, 'city' => 'دمشق'],
        ];
    }

    public static function citizenReports(): array
    {
        return [
            ['title' => 'سيارة متوقفة على الرصيف', 'reporter_name' => 'هبة الخطيب', 'reporter_phone' => '0944112233', 'reporter_email' => 'heba@example.com', 'report_location_landmark' => 'مقابل الصيدلية', 'description' => 'هناك سيارة سيدان بيضاء متوقفة على الرصيف منذ أكثر من ساعة وتمنع مرور المشاة بشكل كامل.', 'image_path' => 'reports/sidewalk-blocking-1.jpg', 'status' => 'closed', 'priority' => 'medium', 'assigned_officer_email' => 'officer.ahmad@traffic.local', 'submitted_days_ago' => 6, 'submitted_hours_ago' => 3, 'accepted_delay_minutes' => 20, 'close_delay_minutes' => 110, 'dispatch_attempts_count' => 1, 'assignment_status' => 'accepted', 'distance_km' => 1.35, 'notes' => 'تم الوصول بسرعة إلى الموقع وتوثيق المخالفة وتنظيم الضبط.'],
            ['title' => 'سائق بلا أوراق نظامية', 'reporter_name' => 'عمر دباغ', 'reporter_phone' => '0944556677', 'reporter_email' => 'omar@example.com', 'report_location_landmark' => 'بجانب جسر المشاة', 'description' => 'بعد احتكاك مروري بسيط، تبيّن أن السائق لا يحمل رخصة قيادة سارية ولا أوراق المركبة الكاملة.', 'image_path' => 'reports/license-check-2.jpg', 'status' => 'in_progress', 'priority' => 'high', 'assigned_officer_email' => 'officer.samer@traffic.local', 'submitted_days_ago' => 4, 'submitted_hours_ago' => 5, 'accepted_delay_minutes' => 25, 'close_delay_minutes' => null, 'dispatch_attempts_count' => 2, 'assignment_status' => 'accepted', 'distance_km' => 2.10, 'notes' => 'يجري التحقق من الوثائق وإحالة السائق للإجراءات النظامية.'],
            ['title' => 'مركبة تعيق مسار الطوارئ', 'reporter_name' => 'لما سعد', 'reporter_phone' => '0944889900', 'reporter_email' => 'lama@example.com', 'report_location_landmark' => 'عند علامة مسار الطوارئ 4', 'description' => 'سيارة سوداء تقف داخل مسار الطوارئ في وقت الذروة وتعرقل مرور مركبات الإسعاف.', 'image_path' => 'reports/emergency-lane-3.jpg', 'status' => 'dispatched', 'priority' => 'urgent', 'assigned_officer_email' => 'officer.lina@traffic.local', 'submitted_days_ago' => 1, 'submitted_hours_ago' => 2, 'accepted_delay_minutes' => 15, 'close_delay_minutes' => null, 'dispatch_attempts_count' => 1, 'assignment_status' => 'en_route', 'distance_km' => 0.85, 'notes' => 'أرسلت الدورية بشكل عاجل بسبب تأثير البلاغ على السلامة العامة.'],
            ['title' => 'استخدام الهاتف أثناء القيادة', 'reporter_name' => 'مجد الخلف', 'reporter_phone' => '0944223344', 'reporter_email' => 'majd@example.com', 'report_location_landmark' => 'بالقرب من المخبز', 'description' => 'السائق كان يستخدم الهاتف المحمول بشكل متكرر أثناء عبور تقاطع مزدحم، ما شكّل خطراً على المركبات والمشاة.', 'image_path' => 'reports/phone-use-4.jpg', 'status' => 'closed', 'priority' => 'medium', 'assigned_officer_email' => 'officer.yousef@traffic.local', 'submitted_days_ago' => 3, 'submitted_hours_ago' => 1, 'accepted_delay_minutes' => 18, 'close_delay_minutes' => 75, 'dispatch_attempts_count' => 1, 'assignment_status' => 'accepted', 'distance_km' => 1.10, 'notes' => 'تم تأكيد البلاغ ميدانياً من قبل الشرطي وتحرير المخالفة.'],
            ['title' => 'مركبة تسير ليلاً دون أضواء', 'reporter_name' => 'رشا العلي', 'reporter_phone' => '0944998877', 'reporter_email' => 'rasha@example.com', 'report_location_landmark' => 'عند بوابة الجامعة', 'description' => 'مركبة رمادية تسير بعد الغروب دون تشغيل الأضواء الأمامية قرب موقف الحافلات.', 'image_path' => 'reports/no-lights-5.jpg', 'status' => 'submitted', 'priority' => 'medium', 'assigned_officer_email' => 'officer.maha@traffic.local', 'submitted_days_ago' => 2, 'submitted_hours_ago' => 6, 'accepted_delay_minutes' => null, 'close_delay_minutes' => null, 'dispatch_attempts_count' => 1, 'assignment_status' => 'pending', 'distance_km' => 1.75, 'notes' => 'بانتظار التحقق الميداني من البلاغ.'],
            ['title' => 'وقوف مزدوج أمام المحال', 'reporter_name' => 'ولاء طه', 'reporter_phone' => '0944332211', 'reporter_email' => 'walaa@example.com', 'report_location_landmark' => 'بالقرب من فرع المصرف', 'description' => 'سيارتان متوقفتان بشكل مزدوج وتمنعان مرور المركبات في أحد المسارب التجارية.', 'image_path' => 'reports/double-parking-6.jpg', 'status' => 'closed', 'priority' => 'low', 'assigned_officer_email' => 'officer.ahmad@traffic.local', 'submitted_days_ago' => 5, 'submitted_hours_ago' => 4, 'accepted_delay_minutes' => 30, 'close_delay_minutes' => 95, 'dispatch_attempts_count' => 1, 'assignment_status' => 'accepted', 'distance_km' => 2.40, 'notes' => 'تمت إزالة المركبتين وتنظيم الضبط اللازم بحق السائقين.'],
            ['title' => 'سير بعكس الاتجاه في شارع فرعي', 'reporter_name' => 'حسام مرديني', 'reporter_phone' => '0944551122', 'reporter_email' => 'hussam@example.com', 'report_location_landmark' => 'بجانب جسر المشاة', 'description' => 'مركبة دخلت إلى الطريق الخدمي أحادي الاتجاه من الجهة المعاكسة بشكل خطر.', 'image_path' => 'reports/wrong-way-7.jpg', 'status' => 'in_progress', 'priority' => 'high', 'assigned_officer_email' => 'officer.samer@traffic.local', 'submitted_days_ago' => 1, 'submitted_hours_ago' => 8, 'accepted_delay_minutes' => 22, 'close_delay_minutes' => null, 'dispatch_attempts_count' => 1, 'assignment_status' => 'accepted', 'distance_km' => 0.95, 'notes' => 'تم طلب تسجيلات الكاميرات القريبة لدعم التوثيق.'],
            ['title' => 'مركبة بحمولة زائدة', 'reporter_name' => 'رامي جبر', 'reporter_phone' => '0944776655', 'reporter_email' => 'rami@example.com', 'report_location_landmark' => 'عند بوابة الجامعة', 'description' => 'مركبة صغيرة تحمل حمولة زائدة تحجب الرؤية الخلفية وتشكل خطراً على مستخدمي الطريق.', 'image_path' => 'reports/overload-8.jpg', 'status' => 'submitted', 'priority' => 'medium', 'assigned_officer_email' => 'officer.maha@traffic.local', 'submitted_days_ago' => 2, 'submitted_hours_ago' => 2, 'accepted_delay_minutes' => null, 'close_delay_minutes' => null, 'dispatch_attempts_count' => 2, 'assignment_status' => 'pending', 'distance_km' => 1.60, 'notes' => 'تم إنشاء محاولة إسناد ثانية بسبب الازدحام وتأخر الوصول.'],
            ['title' => 'بلاغ بانتظار التوزيع قرب الكراج', 'reporter_name' => 'ميساء حيدر', 'reporter_phone' => '0944661122', 'reporter_email' => 'maysa@example.com', 'report_location_landmark' => 'عند بوابة الجامعة', 'description' => 'مركبة متوقفة بشكل مخالف قرب مدخل الكراج وتسبب ازدحاماً متكرراً، ولم يتم إسناد شرطي لها بعد.', 'image_path' => 'reports/unassigned-garage-9.jpg', 'status' => 'submitted', 'priority' => 'high', 'assigned_officer_email' => null, 'submitted_days_ago' => 0, 'submitted_hours_ago' => 5, 'accepted_delay_minutes' => null, 'close_delay_minutes' => null, 'dispatch_attempts_count' => 0, 'assignment_status' => 'pending', 'distance_km' => null, 'notes' => 'البلاغ ما زال بانتظار تعيين شرطي مناسب.'],
            ['title' => 'سيارة تسد ممر المشاة دون إسناد', 'reporter_name' => 'نور سمان', 'reporter_phone' => '0944338871', 'reporter_email' => 'nour.samman@example.com', 'report_location_landmark' => 'مقابل الصيدلية', 'description' => 'السيارة متوقفة مباشرة فوق ممر المشاة وتمنع عبور الأهالي بشكل آمن، ولم يتم إرسال دورية لها حتى الآن.', 'image_path' => 'reports/unassigned-crosswalk-10.jpg', 'status' => 'submitted', 'priority' => 'medium', 'assigned_officer_email' => null, 'submitted_days_ago' => 0, 'submitted_hours_ago' => 2, 'accepted_delay_minutes' => null, 'close_delay_minutes' => null, 'dispatch_attempts_count' => 0, 'assignment_status' => 'pending', 'distance_km' => null, 'notes' => 'بانتظار الإسناد الأول من غرفة العمليات.'],
        ];
    }

    public static function violations(): array
    {
        return [
            ['plate_number' => 'دمشق 123456', 'violation_type_name' => 'الوقوف في مكان ممنوع', 'reported_by_email' => 'officer.ahmad@traffic.local', 'location_landmark' => 'بجانب مدخل المصرف', 'source_report_title' => 'سيارة متوقفة على الرصيف', 'description' => 'تبيّن بعد الكشف أن المركبة متوقفة ضمن منطقة ممنوع الوقوف وعلى جزء من الرصيف.', 'plate_snapshot' => 'plates/illegal-parking-1.jpg', 'owner_snapshot' => 'owners/fares-mahmoud-id.jpg', 'occurred_days_ago' => 6, 'occurred_hours_ago' => 2, 'data_source' => 'citizen_report', 'is_synthetic' => false, 'severity_level' => 'medium', 'status' => 'issued'],
            ['plate_number' => 'دمشق 654321', 'violation_type_name' => 'القيادة بدون رخصة', 'reported_by_email' => 'officer.samer@traffic.local', 'location_landmark' => 'عند الإشارة الضوئية B2', 'source_report_title' => 'سائق بلا أوراق نظامية', 'description' => 'لم يبرز السائق رخصة قيادة سارية أثناء التحقق الميداني بعد البلاغ.', 'plate_snapshot' => 'plates/license-check-2.jpg', 'owner_snapshot' => 'owners/nour-hasan-id.jpg', 'occurred_days_ago' => 4, 'occurred_hours_ago' => 4, 'data_source' => 'citizen_report', 'is_synthetic' => false, 'severity_level' => 'high', 'status' => 'under_appeal'],
            ['plate_number' => 'ريف دمشق 778899', 'violation_type_name' => 'القيادة في مناطق ممنوعة', 'reported_by_email' => 'officer.lina@traffic.local', 'location_landmark' => 'ضمن مسار الطوارئ', 'source_report_title' => 'مركبة تعيق مسار الطوارئ', 'description' => 'تم ضبط المركبة داخل مسار الطوارئ بما يعرقل مرور مركبات الإسعاف والإنقاذ.', 'plate_snapshot' => 'plates/emergency-lane-3.jpg', 'owner_snapshot' => 'owners/khaled-othman-id.jpg', 'occurred_days_ago' => 1, 'occurred_hours_ago' => 1, 'data_source' => 'citizen_report', 'is_synthetic' => false, 'severity_level' => 'critical', 'status' => 'issued'],
            ['plate_number' => 'دمشق 112233', 'violation_type_name' => 'استخدام الهاتف أثناء القيادة', 'reported_by_email' => 'officer.yousef@traffic.local', 'location_landmark' => 'قبل العيادة', 'source_report_title' => 'استخدام الهاتف أثناء القيادة', 'description' => 'أكد الشرطي استخدام السائق للهاتف أثناء حركة السير ضمن تقاطع نشط.', 'plate_snapshot' => 'plates/phone-use-4.jpg', 'owner_snapshot' => 'owners/maya-youssef-id.jpg', 'occurred_days_ago' => 3, 'occurred_hours_ago' => 1, 'data_source' => 'citizen_report', 'is_synthetic' => false, 'severity_level' => 'medium', 'status' => 'issued'],
            ['plate_number' => 'دمشق 223344', 'violation_type_name' => 'عدم تشغيل الأضواء ليلاً', 'reported_by_email' => 'officer.maha@traffic.local', 'location_landmark' => 'عند مدخل الكلية', 'source_report_title' => 'مركبة تسير ليلاً دون أضواء', 'description' => 'شوهدت المركبة تسير في ظروف رؤية منخفضة من دون تشغيل الأضواء الأمامية المطلوبة.', 'plate_snapshot' => 'plates/no-lights-5.jpg', 'owner_snapshot' => 'owners/hadi-ibrahim-id.jpg', 'occurred_days_ago' => 2, 'occurred_hours_ago' => 5, 'data_source' => 'citizen_report', 'is_synthetic' => false, 'severity_level' => 'low', 'status' => 'issued'],
            ['plate_number' => 'دمشق 334455', 'violation_type_name' => 'الوقوف المزدوج', 'reported_by_email' => 'officer.ahmad@traffic.local', 'location_landmark' => 'بالقرب من ممر المشاة الضوئي', 'source_report_title' => 'وقوف مزدوج أمام المحال', 'description' => 'كانت المركبة تعيق أحد المسارب بسبب الوقوف المزدوج أمام المتاجر.', 'plate_snapshot' => 'plates/double-parking-6.jpg', 'owner_snapshot' => 'owners/dina-qutob-id.jpg', 'occurred_days_ago' => 5, 'occurred_hours_ago' => 3, 'data_source' => 'citizen_report', 'is_synthetic' => false, 'severity_level' => 'low', 'status' => 'issued'],
            ['plate_number' => 'دمشق 445566', 'violation_type_name' => 'السير بعكس الاتجاه', 'reported_by_email' => 'officer.samer@traffic.local', 'location_landmark' => 'عند الإشارة الضوئية B2', 'source_report_title' => 'سير بعكس الاتجاه في شارع فرعي', 'description' => 'دخل السائق إلى طريق أحادي الاتجاه من الجهة المعاكسة بشكل مباشر.', 'plate_snapshot' => 'plates/wrong-way-7.jpg', 'owner_snapshot' => 'owners/rami-alzein-id.jpg', 'occurred_days_ago' => 1, 'occurred_hours_ago' => 7, 'data_source' => 'citizen_report', 'is_synthetic' => false, 'severity_level' => 'critical', 'status' => 'under_review'],
            ['plate_number' => 'دمشق 556677', 'violation_type_name' => 'تحميل ركاب أو حمولة زائدة', 'reported_by_email' => 'officer.maha@traffic.local', 'location_landmark' => 'عند مدخل الكلية', 'source_report_title' => 'مركبة بحمولة زائدة', 'description' => 'تجاوزت المركبة الحمولة المسموح بها وحجبت الرؤية الخلفية بشكل واضح.', 'plate_snapshot' => 'plates/overload-8.jpg', 'owner_snapshot' => 'owners/salma-hariri-id.jpg', 'occurred_days_ago' => 2, 'occurred_hours_ago' => 1, 'data_source' => 'citizen_report', 'is_synthetic' => false, 'severity_level' => 'medium', 'status' => 'issued'],
            ['plate_number' => 'ريف دمشق 667788', 'violation_type_name' => 'تجاوز السرعة المحددة', 'reported_by_email' => 'officer.ahmad@traffic.local', 'location_landmark' => 'ضمن مسار الطوارئ', 'source_report_title' => null, 'description' => 'التقط الرادار سرعة أعلى من الحد المسموح أثناء جولة دورية ضمن المنطقة.', 'plate_snapshot' => 'plates/speed-9.jpg', 'owner_snapshot' => 'owners/anas-dabbas-id.jpg', 'occurred_days_ago' => 0, 'occurred_hours_ago' => 10, 'data_source' => 'patrol', 'is_synthetic' => false, 'severity_level' => 'medium', 'status' => 'issued'],
            ['plate_number' => 'دمشق 778811', 'violation_type_name' => 'قطع الإشارة الحمراء', 'reported_by_email' => 'officer.yousef@traffic.local', 'location_landmark' => 'بالقرب من المخبز', 'source_report_title' => null, 'description' => 'أكدت الكاميرا وملاحظات الشرطي عبور المركبة للتقاطع بعد ظهور الإشارة الحمراء.', 'plate_snapshot' => 'plates/red-light-10.jpg', 'owner_snapshot' => 'owners/lama-hajj-id.jpg', 'occurred_days_ago' => 0, 'occurred_hours_ago' => 6, 'data_source' => 'camera', 'is_synthetic' => false, 'severity_level' => 'critical', 'status' => 'issued'],
            ['plate_number' => 'دمشق 889922', 'violation_type_name' => 'عدم وجود لوحات للمركبة', 'reported_by_email' => 'officer.lina@traffic.local', 'location_landmark' => 'بجانب مدخل المصرف', 'source_report_title' => null, 'description' => 'شوهدت المركبة تسير في الطريق العام من دون لوحات تعريف واضحة أو مثبتة.', 'plate_snapshot' => 'plates/no-plates-11.jpg', 'owner_snapshot' => 'owners/qais-nabulsi-id.jpg', 'occurred_days_ago' => 0, 'occurred_hours_ago' => 4, 'data_source' => 'patrol', 'is_synthetic' => false, 'severity_level' => 'high', 'status' => 'issued'],
            ['plate_number' => 'دمشق 991133', 'violation_type_name' => 'رمي النفايات من المركبة', 'reported_by_email' => 'officer.maha@traffic.local', 'location_landmark' => 'بالقرب من ممر المشاة الضوئي', 'source_report_title' => null, 'description' => 'تمت ملاحظة أحد الركاب وهو يرمي النفايات من المركبة أثناء السير.', 'plate_snapshot' => 'plates/littering-12.jpg', 'owner_snapshot' => 'owners/hiba-darwish-id.jpg', 'occurred_days_ago' => 0, 'occurred_hours_ago' => 3, 'data_source' => 'patrol', 'is_synthetic' => false, 'severity_level' => 'low', 'status' => 'issued'],
        ];
    }

    public static function liveLocations(): array
    {
        return [
            ['officer_email' => 'officer.ahmad@traffic.local', 'latitude' => 33.5143020, 'longitude' => 36.2761320, 'availability_status' => 'available'],
            ['officer_email' => 'officer.samer@traffic.local', 'latitude' => 33.4861820, 'longitude' => 36.2550310, 'availability_status' => 'available'],
            ['officer_email' => 'officer.lina@traffic.local', 'latitude' => 33.5088420, 'longitude' => 36.2332100, 'availability_status' => 'responding'],
            ['officer_email' => 'officer.yousef@traffic.local', 'latitude' => 33.5172140, 'longitude' => 36.2845010, 'availability_status' => 'available'],
            ['officer_email' => 'officer.maha@traffic.local', 'latitude' => 33.5095110, 'longitude' => 36.2921800, 'availability_status' => 'available'],
        ];
    }

    public static function appeals(): array
    {
        return [
            ['plate_snapshot' => 'plates/license-check-2.jpg', 'status' => 'pending', 'reason' => 'أفاد السائق أن النسخة الأصلية من الرخصة كانت في المنزل وطلب مراجعة إدارية للمخالفة.', 'decision_note' => null, 'submitted_days_ago' => 3, 'submitted_hours_ago' => 10, 'decided_days_ago' => null, 'decided_hours_ago' => null],
            ['plate_snapshot' => 'plates/double-parking-6.jpg', 'status' => 'accepted', 'reason' => 'ذكر مالك المركبة أن التوقف كان مؤقتاً لإنزال راكب من ذوي الاحتياجات الخاصة.', 'decision_note' => 'تم قبول المستندات المؤيدة وإلغاء الغرامة المترتبة على المخالفة.', 'submitted_days_ago' => 4, 'submitted_hours_ago' => 8, 'decided_days_ago' => 2, 'decided_hours_ago' => 4],
            ['plate_snapshot' => 'plates/no-lights-5.jpg', 'status' => 'rejected', 'reason' => 'أوضح صاحب المركبة أن الأضواء تعطلت بشكل مفاجئ أثناء السير.', 'decision_note' => 'أظهر تسجيل الكاميرا أن الخلل كان قائماً قبل الانطلاق، لذلك رُفض الاعتراض.', 'submitted_days_ago' => 1, 'submitted_hours_ago' => 18, 'decided_days_ago' => 0, 'decided_hours_ago' => 6],
        ];
    }
}
