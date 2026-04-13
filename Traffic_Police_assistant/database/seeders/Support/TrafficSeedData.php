<?php

namespace Database\Seeders\Support;

class TrafficSeedData
{
    public static function users(): array
    {
        return [
            'admin' => [
                'name' => 'Sami Al-Haddad',
                'email' => 'admin@traffic.local',
                'phone' => '0933000001',
                'role' => 'admin',
                'profile_image' => 'profiles/admin-sami.jpg',
                'is_active' => true,
                'last_seen_minutes_ago' => 5,
            ],
            'manager' => [
                'name' => 'Rana Al-Masri',
                'email' => 'manager@traffic.local',
                'phone' => '0933000002',
                'role' => 'Police_manager',
                'profile_image' => 'profiles/manager-rana.jpg',
                'is_active' => true,
                'last_seen_minutes_ago' => 8,
            ],
            'officers' => [
                [
                    'name' => 'Ahmad Nasser',
                    'email' => 'officer.ahmad@traffic.local',
                    'phone' => '0933000003',
                    'role' => 'Police_officer',
                    'profile_image' => 'profiles/officer-ahmad.jpg',
                    'is_active' => true,
                    'last_seen_minutes_ago' => 15,
                ],
                [
                    'name' => 'Samer Darwish',
                    'email' => 'officer.samer@traffic.local',
                    'phone' => '0933000004',
                    'role' => 'Police_officer',
                    'profile_image' => 'profiles/officer-samer.jpg',
                    'is_active' => true,
                    'last_seen_minutes_ago' => 18,
                ],
                [
                    'name' => 'Lina Qassab',
                    'email' => 'officer.lina@traffic.local',
                    'phone' => '0933000005',
                    'role' => 'Police_officer',
                    'profile_image' => 'profiles/officer-lina.jpg',
                    'is_active' => true,
                    'last_seen_minutes_ago' => 21,
                ],
                [
                    'name' => 'Yousef Al-Ali',
                    'email' => 'officer.yousef@traffic.local',
                    'phone' => '0933000006',
                    'role' => 'Police_officer',
                    'profile_image' => 'profiles/officer-yousef.jpg',
                    'is_active' => true,
                    'last_seen_minutes_ago' => 11,
                ],
                [
                    'name' => 'Maha Suleiman',
                    'email' => 'officer.maha@traffic.local',
                    'phone' => '0933000007',
                    'role' => 'Police_officer',
                    'profile_image' => 'profiles/officer-maha.jpg',
                    'is_active' => true,
                    'last_seen_minutes_ago' => 14,
                ],
                [
                    'name' => 'Tariq Hamed',
                    'email' => 'officer.tariq@traffic.local',
                    'phone' => '0933000008',
                    'role' => 'Police_officer',
                    'profile_image' => 'profiles/officer-tariq.jpg',
                    'is_active' => false,
                    'last_seen_minutes_ago' => 240,
                ],
            ],
        ];
    }

    public static function areas(): array
    {
        return [
            ['name' => 'Al-Malki', 'city' => 'Damascus', 'center_lat' => 33.5145221, 'center_lng' => 36.2765127],
            ['name' => 'Kafr Sousa', 'city' => 'Damascus', 'center_lat' => 33.4862105, 'center_lng' => 36.2549003],
            ['name' => 'Mazzeh', 'city' => 'Damascus', 'center_lat' => 33.5091842, 'center_lng' => 36.2329508],
            ['name' => 'Abu Rummaneh', 'city' => 'Damascus', 'center_lat' => 33.5179031, 'center_lng' => 36.2841402],
            ['name' => 'Baramkeh', 'city' => 'Damascus', 'center_lat' => 33.5085584, 'center_lng' => 36.2924922],
            ['name' => 'Al-Hamra', 'city' => 'Damascus', 'center_lat' => 33.5131816, 'center_lng' => 36.3006549],
        ];
    }

    public static function vehicles(): array
    {
        return [
            ['plate_number' => 'دمشق 123456', 'owner_name' => 'Fares Mahmoud', 'model' => 'Kia Rio 2018', 'color' => 'White'],
            ['plate_number' => 'دمشق 654321', 'owner_name' => 'Nour Hasan', 'model' => 'Hyundai Accent 2020', 'color' => 'Silver'],
            ['plate_number' => 'ريف دمشق 778899', 'owner_name' => 'Khaled Othman', 'model' => 'Toyota Corolla 2017', 'color' => 'Black'],
            ['plate_number' => 'دمشق 112233', 'owner_name' => 'Maya Youssef', 'model' => 'Nissan Sunny 2019', 'color' => 'Blue'],
            ['plate_number' => 'دمشق 223344', 'owner_name' => 'Hadi Ibrahim', 'model' => 'Toyota Yaris 2016', 'color' => 'Gray'],
            ['plate_number' => 'دمشق 334455', 'owner_name' => 'Dina Qutob', 'model' => 'Kia Cerato 2021', 'color' => 'Red'],
            ['plate_number' => 'دمشق 445566', 'owner_name' => 'Rami Al-Zein', 'model' => 'Hyundai Elantra 2018', 'color' => 'White'],
            ['plate_number' => 'دمشق 556677', 'owner_name' => 'Salma Hariri', 'model' => 'Suzuki Swift 2015', 'color' => 'Yellow'],
            ['plate_number' => 'ريف دمشق 667788', 'owner_name' => 'Anas Dabbas', 'model' => 'Renault Logan 2014', 'color' => 'Silver'],
            ['plate_number' => 'دمشق 778811', 'owner_name' => 'Lama Hajj', 'model' => 'Honda Civic 2022', 'color' => 'Black'],
            ['plate_number' => 'دمشق 889922', 'owner_name' => 'Qais Nabulsi', 'model' => 'Peugeot 301 2019', 'color' => 'Blue'],
            ['plate_number' => 'دمشق 991133', 'owner_name' => 'Hiba Darwish', 'model' => 'Toyota Camry 2020', 'color' => 'White'],
            ['plate_number' => 'ريف دمشق 224466', 'owner_name' => 'Mazen Sabbagh', 'model' => 'Mitsubishi Lancer 2013', 'color' => 'Gray'],
            ['plate_number' => 'دمشق 335577', 'owner_name' => 'Nisreen Alwan', 'model' => 'Kia Picanto 2017', 'color' => 'Pink'],
            ['plate_number' => 'دمشق 446688', 'owner_name' => 'Wael Hamdan', 'model' => 'Hyundai Tucson 2021', 'color' => 'Green'],
        ];
    }

    public static function reportLocations(): array
    {
        return [
            ['area_name' => 'Al-Malki', 'address' => 'Near Al-Malki roundabout', 'street_name' => 'Al-Malki Street', 'landmark' => 'Opposite the pharmacy', 'latitude' => 33.5139550, 'longitude' => 36.2759020, 'city' => 'Damascus'],
            ['area_name' => 'Kafr Sousa', 'address' => 'Kafr Sousa service lane', 'street_name' => 'Kafr Sousa Main Street', 'landmark' => 'Next to the pedestrian bridge', 'latitude' => 33.4858710, 'longitude' => 36.2551420, 'city' => 'Damascus'],
            ['area_name' => 'Mazzeh', 'address' => 'Mazzeh highway entrance', 'street_name' => 'Mazzeh Highway', 'landmark' => 'Emergency lane marker 4', 'latitude' => 33.5081140, 'longitude' => 36.2336110, 'city' => 'Damascus'],
            ['area_name' => 'Abu Rummaneh', 'address' => 'Abu Rummaneh square', 'street_name' => 'Shukri Al-Quwatli Street', 'landmark' => 'Near the bakery', 'latitude' => 33.5177520, 'longitude' => 36.2838140, 'city' => 'Damascus'],
            ['area_name' => 'Baramkeh', 'address' => 'Baramkeh bus stop', 'street_name' => 'Al-Thawra Street', 'landmark' => 'University gate', 'latitude' => 33.5086840, 'longitude' => 36.2917810, 'city' => 'Damascus'],
            ['area_name' => 'Al-Hamra', 'address' => 'Hamra commercial street', 'street_name' => 'Al-Hamra Street', 'landmark' => 'Near the bank branch', 'latitude' => 33.5129160, 'longitude' => 36.3003550, 'city' => 'Damascus'],
        ];
    }

    public static function violationLocations(): array
    {
        return [
            ['area_name' => 'Al-Malki', 'address' => 'Al-Malki curbside', 'street_name' => 'Al-Malki Street', 'landmark' => 'Beside the bank entrance', 'latitude' => 33.5141020, 'longitude' => 36.2762010, 'city' => 'Damascus'],
            ['area_name' => 'Kafr Sousa', 'address' => 'Kafr Sousa junction', 'street_name' => 'Kafr Sousa Main Street', 'landmark' => 'Traffic light B2', 'latitude' => 33.4864120, 'longitude' => 36.2545010, 'city' => 'Damascus'],
            ['area_name' => 'Mazzeh', 'address' => 'Mazzeh westbound lane', 'street_name' => 'Mazzeh Highway', 'landmark' => 'Emergency lane section', 'latitude' => 33.5090110, 'longitude' => 36.2328870, 'city' => 'Damascus'],
            ['area_name' => 'Abu Rummaneh', 'address' => 'Abu Rummaneh parking strip', 'street_name' => 'Shukri Al-Quwatli Street', 'landmark' => 'Before the clinic', 'latitude' => 33.5172110, 'longitude' => 36.2843330, 'city' => 'Damascus'],
            ['area_name' => 'Abu Rummaneh', 'address' => 'Abu Rummaneh intersection', 'street_name' => 'Shukri Al-Quwatli Street', 'landmark' => 'Near the bakery', 'latitude' => 33.5176410, 'longitude' => 36.2839550, 'city' => 'Damascus'],
            ['area_name' => 'Baramkeh', 'address' => 'Baramkeh crossing', 'street_name' => 'Al-Thawra Street', 'landmark' => 'Faculty entrance', 'latitude' => 33.5091140, 'longitude' => 36.2924170, 'city' => 'Damascus'],
            ['area_name' => 'Al-Hamra', 'address' => 'Hamra retail lane', 'street_name' => 'Al-Hamra Street', 'landmark' => 'Near the signalized crossing', 'latitude' => 33.5126210, 'longitude' => 36.3010810, 'city' => 'Damascus'],
        ];
    }

    public static function citizenReports(): array
    {
        return [
            ['title' => 'Car parked on sidewalk', 'reporter_name' => 'Heba Al-Khatib', 'reporter_phone' => '0944112233', 'reporter_email' => 'heba@example.com', 'report_location_landmark' => 'Opposite the pharmacy', 'description' => 'A white sedan has been blocking the sidewalk for over an hour and pedestrians are forced into the road.', 'image_path' => 'reports/sidewalk-blocking-1.jpg', 'status' => 'closed', 'priority' => 'medium', 'assigned_officer_email' => 'officer.ahmad@traffic.local', 'submitted_days_ago' => 6, 'submitted_hours_ago' => 3, 'accepted_delay_minutes' => 20, 'close_delay_minutes' => 110, 'dispatch_attempts_count' => 1, 'assignment_status' => 'accepted', 'distance_km' => 1.35, 'notes' => 'Reached site quickly and documented the violation.'],
            ['title' => 'Unsafe driver without papers', 'reporter_name' => 'Omar Dabbagh', 'reporter_phone' => '0944556677', 'reporter_email' => 'omar@example.com', 'report_location_landmark' => 'Next to the pedestrian bridge', 'description' => 'The driver was stopped after a minor collision and could not present a valid driving license.', 'image_path' => 'reports/license-check-2.jpg', 'status' => 'under_review', 'priority' => 'high', 'assigned_officer_email' => 'officer.samer@traffic.local', 'submitted_days_ago' => 4, 'submitted_hours_ago' => 5, 'accepted_delay_minutes' => 25, 'close_delay_minutes' => null, 'dispatch_attempts_count' => 2, 'assignment_status' => 'accepted', 'distance_km' => 2.10, 'notes' => 'Driver documents under verification.'],
            ['title' => 'Vehicle blocking emergency lane', 'reporter_name' => 'Lama Saad', 'reporter_phone' => '0944889900', 'reporter_email' => 'lama@example.com', 'report_location_landmark' => 'Emergency lane marker 4', 'description' => 'A black sedan remained in the emergency lane during peak traffic and blocked a medical transport route.', 'image_path' => 'reports/emergency-lane-3.jpg', 'status' => 'dispatched', 'priority' => 'urgent', 'assigned_officer_email' => 'officer.lina@traffic.local', 'submitted_days_ago' => 1, 'submitted_hours_ago' => 2, 'accepted_delay_minutes' => 15, 'close_delay_minutes' => null, 'dispatch_attempts_count' => 1, 'assignment_status' => 'en_route', 'distance_km' => 0.85, 'notes' => 'Priority dispatch for lane obstruction.'],
            ['title' => 'Driver using phone in traffic', 'reporter_name' => 'Majd Al-Khalaf', 'reporter_phone' => '0944223344', 'reporter_email' => 'majd@example.com', 'report_location_landmark' => 'Near the bakery', 'description' => 'The driver was repeatedly texting while moving through a busy intersection.', 'image_path' => 'reports/phone-use-4.jpg', 'status' => 'closed', 'priority' => 'medium', 'assigned_officer_email' => 'officer.yousef@traffic.local', 'submitted_days_ago' => 3, 'submitted_hours_ago' => 1, 'accepted_delay_minutes' => 18, 'close_delay_minutes' => 75, 'dispatch_attempts_count' => 1, 'assignment_status' => 'accepted', 'distance_km' => 1.10, 'notes' => 'Violation confirmed by officer body camera.'],
            ['title' => 'No lights at night', 'reporter_name' => 'Rasha Al-Ali', 'reporter_phone' => '0944998877', 'reporter_email' => 'rasha@example.com', 'report_location_landmark' => 'University gate', 'description' => 'A gray car was driving without headlights after sunset near the bus stop.', 'image_path' => 'reports/no-lights-5.jpg', 'status' => 'submitted', 'priority' => 'medium', 'assigned_officer_email' => 'officer.maha@traffic.local', 'submitted_days_ago' => 2, 'submitted_hours_ago' => 6, 'accepted_delay_minutes' => null, 'close_delay_minutes' => null, 'dispatch_attempts_count' => 1, 'assignment_status' => 'pending', 'distance_km' => 1.75, 'notes' => 'Awaiting field verification.'],
            ['title' => 'Double parking near shops', 'reporter_name' => 'Walaa Taha', 'reporter_phone' => '0944332211', 'reporter_email' => 'walaa@example.com', 'report_location_landmark' => 'Near the bank branch', 'description' => 'Two vehicles are double parked and blocking one of the commercial lanes.', 'image_path' => 'reports/double-parking-6.jpg', 'status' => 'closed', 'priority' => 'low', 'assigned_officer_email' => 'officer.ahmad@traffic.local', 'submitted_days_ago' => 5, 'submitted_hours_ago' => 4, 'accepted_delay_minutes' => 30, 'close_delay_minutes' => 95, 'dispatch_attempts_count' => 1, 'assignment_status' => 'accepted', 'distance_km' => 2.40, 'notes' => 'Vehicles were cleared and tickets issued.'],
            ['title' => 'Wrong-way driving at side street', 'reporter_name' => 'Hussam Mardini', 'reporter_phone' => '0944551122', 'reporter_email' => 'hussam@example.com', 'report_location_landmark' => 'Next to the pedestrian bridge', 'description' => 'A vehicle entered the one-way service road from the opposite direction.', 'image_path' => 'reports/wrong-way-7.jpg', 'status' => 'under_review', 'priority' => 'high', 'assigned_officer_email' => 'officer.samer@traffic.local', 'submitted_days_ago' => 1, 'submitted_hours_ago' => 8, 'accepted_delay_minutes' => 22, 'close_delay_minutes' => null, 'dispatch_attempts_count' => 1, 'assignment_status' => 'accepted', 'distance_km' => 0.95, 'notes' => 'Nearby cameras requested for confirmation.'],
            ['title' => 'Overloaded vehicle', 'reporter_name' => 'Rami Jabbour', 'reporter_phone' => '0944776655', 'reporter_email' => 'rami@example.com', 'report_location_landmark' => 'University gate', 'description' => 'A small vehicle was carrying excessive cargo and obscuring rear visibility.', 'image_path' => 'reports/overload-8.jpg', 'status' => 'submitted', 'priority' => 'medium', 'assigned_officer_email' => 'officer.maha@traffic.local', 'submitted_days_ago' => 2, 'submitted_hours_ago' => 2, 'accepted_delay_minutes' => null, 'close_delay_minutes' => null, 'dispatch_attempts_count' => 2, 'assignment_status' => 'pending', 'distance_km' => 1.60, 'notes' => 'Second dispatch attempt created due to heavy traffic.'],
        ];
    }

    public static function violations(): array
    {
        return [
            ['plate_number' => 'دمشق 123456', 'violation_type_name' => 'الوقوف في مكان ممنوع', 'reported_by_email' => 'officer.ahmad@traffic.local', 'location_landmark' => 'Beside the bank entrance', 'source_report_title' => 'Car parked on sidewalk', 'description' => 'Vehicle was confirmed parked in a no-parking sidewalk zone.', 'plate_snapshot' => 'plates/illegal-parking-1.jpg', 'owner_snapshot' => 'owners/fares-mahmoud-id.jpg', 'occurred_days_ago' => 6, 'occurred_hours_ago' => 2, 'data_source' => 'citizen_report', 'is_synthetic' => false, 'severity_level' => 'medium', 'status' => 'issued'],
            ['plate_number' => 'دمشق 654321', 'violation_type_name' => 'القيادة بدون رخصة', 'reported_by_email' => 'officer.samer@traffic.local', 'location_landmark' => 'Traffic light B2', 'source_report_title' => 'Unsafe driver without papers', 'description' => 'Driver did not present a valid license during roadside verification.', 'plate_snapshot' => 'plates/license-check-2.jpg', 'owner_snapshot' => 'owners/nour-hasan-id.jpg', 'occurred_days_ago' => 4, 'occurred_hours_ago' => 4, 'data_source' => 'citizen_report', 'is_synthetic' => false, 'severity_level' => 'high', 'status' => 'under_appeal'],
            ['plate_number' => 'ريف دمشق 778899', 'violation_type_name' => 'القيادة في مناطق ممنوعة', 'reported_by_email' => 'officer.lina@traffic.local', 'location_landmark' => 'Emergency lane section', 'source_report_title' => 'Vehicle blocking emergency lane', 'description' => 'Vehicle blocked emergency lane while traffic officers attempted clearance.', 'plate_snapshot' => 'plates/emergency-lane-3.jpg', 'owner_snapshot' => 'owners/khaled-othman-id.jpg', 'occurred_days_ago' => 1, 'occurred_hours_ago' => 1, 'data_source' => 'citizen_report', 'is_synthetic' => false, 'severity_level' => 'critical', 'status' => 'issued'],
            ['plate_number' => 'دمشق 112233', 'violation_type_name' => 'استخدام الهاتف أثناء القيادة', 'reported_by_email' => 'officer.yousef@traffic.local', 'location_landmark' => 'Before the clinic', 'source_report_title' => 'Driver using phone in traffic', 'description' => 'Patrol officer confirmed mobile phone use through direct observation.', 'plate_snapshot' => 'plates/phone-use-4.jpg', 'owner_snapshot' => 'owners/maya-youssef-id.jpg', 'occurred_days_ago' => 3, 'occurred_hours_ago' => 1, 'data_source' => 'citizen_report', 'is_synthetic' => false, 'severity_level' => 'medium', 'status' => 'issued'],
            ['plate_number' => 'دمشق 223344', 'violation_type_name' => 'عدم تشغيل الأضواء ليلاً', 'reported_by_email' => 'officer.maha@traffic.local', 'location_landmark' => 'Faculty entrance', 'source_report_title' => 'No lights at night', 'description' => 'Vehicle observed without required lighting during low visibility hours.', 'plate_snapshot' => 'plates/no-lights-5.jpg', 'owner_snapshot' => 'owners/hadi-ibrahim-id.jpg', 'occurred_days_ago' => 2, 'occurred_hours_ago' => 5, 'data_source' => 'citizen_report', 'is_synthetic' => false, 'severity_level' => 'low', 'status' => 'issued'],
            ['plate_number' => 'دمشق 334455', 'violation_type_name' => 'الوقوف المزدوج', 'reported_by_email' => 'officer.ahmad@traffic.local', 'location_landmark' => 'Near the signalized crossing', 'source_report_title' => 'Double parking near shops', 'description' => 'Vehicle was blocking one lane due to double parking in a commercial strip.', 'plate_snapshot' => 'plates/double-parking-6.jpg', 'owner_snapshot' => 'owners/dina-qutob-id.jpg', 'occurred_days_ago' => 5, 'occurred_hours_ago' => 3, 'data_source' => 'citizen_report', 'is_synthetic' => false, 'severity_level' => 'low', 'status' => 'issued'],
            ['plate_number' => 'دمشق 445566', 'violation_type_name' => 'السير بعكس الاتجاه', 'reported_by_email' => 'officer.samer@traffic.local', 'location_landmark' => 'Traffic light B2', 'source_report_title' => 'Wrong-way driving at side street', 'description' => 'Driver entered a restricted one-way segment from the opposite side.', 'plate_snapshot' => 'plates/wrong-way-7.jpg', 'owner_snapshot' => 'owners/rami-alzein-id.jpg', 'occurred_days_ago' => 1, 'occurred_hours_ago' => 7, 'data_source' => 'citizen_report', 'is_synthetic' => false, 'severity_level' => 'critical', 'status' => 'under_review'],
            ['plate_number' => 'دمشق 556677', 'violation_type_name' => 'تحميل ركاب أو حمولة زائدة', 'reported_by_email' => 'officer.maha@traffic.local', 'location_landmark' => 'Faculty entrance', 'source_report_title' => 'Overloaded vehicle', 'description' => 'Vehicle exceeded safe cargo capacity and obstructed rear visibility.', 'plate_snapshot' => 'plates/overload-8.jpg', 'owner_snapshot' => 'owners/salma-hariri-id.jpg', 'occurred_days_ago' => 2, 'occurred_hours_ago' => 1, 'data_source' => 'citizen_report', 'is_synthetic' => false, 'severity_level' => 'medium', 'status' => 'issued'],
            ['plate_number' => 'ريف دمشق 667788', 'violation_type_name' => 'تجاوز السرعة المحددة', 'reported_by_email' => 'officer.ahmad@traffic.local', 'location_landmark' => 'Emergency lane section', 'source_report_title' => null, 'description' => 'Speed radar recorded a moderate speed violation during patrol.', 'plate_snapshot' => 'plates/speed-9.jpg', 'owner_snapshot' => 'owners/anas-dabbas-id.jpg', 'occurred_days_ago' => 0, 'occurred_hours_ago' => 10, 'data_source' => 'patrol', 'is_synthetic' => false, 'severity_level' => 'medium', 'status' => 'issued'],
            ['plate_number' => 'دمشق 778811', 'violation_type_name' => 'قطع الإشارة الحمراء', 'reported_by_email' => 'officer.yousef@traffic.local', 'location_landmark' => 'Near the bakery', 'source_report_title' => null, 'description' => 'Intersection camera and officer notes confirmed red-light crossing.', 'plate_snapshot' => 'plates/red-light-10.jpg', 'owner_snapshot' => 'owners/lama-hajj-id.jpg', 'occurred_days_ago' => 0, 'occurred_hours_ago' => 6, 'data_source' => 'camera', 'is_synthetic' => false, 'severity_level' => 'critical', 'status' => 'issued'],
            ['plate_number' => 'دمشق 889922', 'violation_type_name' => 'عدم وجود لوحات للمركبة', 'reported_by_email' => 'officer.lina@traffic.local', 'location_landmark' => 'Beside the bank entrance', 'source_report_title' => null, 'description' => 'Vehicle was moving without visible registration plates.', 'plate_snapshot' => 'plates/no-plates-11.jpg', 'owner_snapshot' => 'owners/qais-nabulsi-id.jpg', 'occurred_days_ago' => 0, 'occurred_hours_ago' => 4, 'data_source' => 'patrol', 'is_synthetic' => false, 'severity_level' => 'high', 'status' => 'issued'],
            ['plate_number' => 'دمشق 991133', 'violation_type_name' => 'رمي النفايات من المركبة', 'reported_by_email' => 'officer.maha@traffic.local', 'location_landmark' => 'Near the signalized crossing', 'source_report_title' => null, 'description' => 'Passenger threw waste from the moving vehicle into the roadway.', 'plate_snapshot' => 'plates/littering-12.jpg', 'owner_snapshot' => 'owners/hiba-darwish-id.jpg', 'occurred_days_ago' => 0, 'occurred_hours_ago' => 3, 'data_source' => 'patrol', 'is_synthetic' => false, 'severity_level' => 'low', 'status' => 'issued'],
        ];
    }

    public static function liveLocations(): array
    {
        return [
            ['officer_email' => 'officer.ahmad@traffic.local', 'latitude' => 33.5143020, 'longitude' => 36.2761320, 'availability_status' => 'available', 'battery_level' => 87],
            ['officer_email' => 'officer.samer@traffic.local', 'latitude' => 33.4861820, 'longitude' => 36.2550310, 'availability_status' => 'busy', 'battery_level' => 72],
            ['officer_email' => 'officer.lina@traffic.local', 'latitude' => 33.5088420, 'longitude' => 36.2332100, 'availability_status' => 'responding', 'battery_level' => 64],
            ['officer_email' => 'officer.yousef@traffic.local', 'latitude' => 33.5172140, 'longitude' => 36.2845010, 'availability_status' => 'available', 'battery_level' => 91],
            ['officer_email' => 'officer.maha@traffic.local', 'latitude' => 33.5095110, 'longitude' => 36.2921800, 'availability_status' => 'available', 'battery_level' => 76],
        ];
    }

    public static function appeals(): array
    {
        return [
            ['plate_snapshot' => 'plates/license-check-2.jpg', 'status' => 'pending', 'reason' => 'The driver claims the original license copy was left at home and requests administrative review.', 'decision_note' => null, 'submitted_days_ago' => 3, 'submitted_hours_ago' => 10, 'decided_days_ago' => null, 'decided_hours_ago' => null],
            ['plate_snapshot' => 'plates/double-parking-6.jpg', 'status' => 'accepted', 'reason' => 'The vehicle was stopped temporarily to unload a disabled passenger.', 'decision_note' => 'Supporting documents were accepted and the fine was waived.', 'submitted_days_ago' => 4, 'submitted_hours_ago' => 8, 'decided_days_ago' => 2, 'decided_hours_ago' => 4],
            ['plate_snapshot' => 'plates/no-lights-5.jpg', 'status' => 'rejected', 'reason' => 'The owner stated the lights failed unexpectedly during the trip.', 'decision_note' => 'Officer bodycam showed the issue persisted before departure; appeal rejected.', 'submitted_days_ago' => 1, 'submitted_hours_ago' => 18, 'decided_days_ago' => 0, 'decided_hours_ago' => 6],
        ];
    }
}
