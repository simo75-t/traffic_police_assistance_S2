<?php

namespace Database\Seeders;

use App\Models\ViolationType;
use Illuminate\Database\Seeder;

class ViolationTypesSeeder extends Seeder
{
    public function run(): void
    {
        $violationTypes = [
            ['name' => 'تجاوز السرعة المحددة', 'description' => 'قيادة المركبة بسرعة تتجاوز الحد المسموح على الطريق.', 'fine_amount' => 25, 'severity_weight' => 3, 'is_active' => true],
            ['name' => 'تجاوز السرعة بنسبة كبيرة', 'description' => 'تجاوز الحد المسموح بفارق كبير يرفع احتمال الحوادث بشكل واضح.', 'fine_amount' => 75, 'severity_weight' => 4, 'is_active' => true],
            ['name' => 'قطع الإشارة الحمراء', 'description' => 'تجاوز الإشارة الضوئية الحمراء قبل السماح بالمرور.', 'fine_amount' => 100, 'severity_weight' => 5, 'is_active' => true],
            ['name' => 'عدم الالتزام بإشارة المرور', 'description' => 'مخالفة التعليمات الصادرة عن الإشارات المرورية أو العلامات المنظمة للطريق.', 'fine_amount' => 50, 'severity_weight' => 3, 'is_active' => true],
            ['name' => 'الوقوف في مكان ممنوع', 'description' => 'إيقاف المركبة في موقع يمنع فيه الوقوف أو يسبب إرباكاً للحركة.', 'fine_amount' => 15, 'severity_weight' => 1, 'is_active' => true],
            ['name' => 'الوقوف المزدوج', 'description' => 'إيقاف المركبة بمحاذاة مركبة أخرى بما يعيق المرور.', 'fine_amount' => 20, 'severity_weight' => 2, 'is_active' => true],
            ['name' => 'عدم ارتداء حزام الأمان', 'description' => 'قيادة المركبة أو مرافقتها من دون استخدام حزام الأمان.', 'fine_amount' => 20, 'severity_weight' => 2, 'is_active' => true],
            ['name' => 'استخدام الهاتف أثناء القيادة', 'description' => 'استخدام الهاتف المحمول أثناء قيادة المركبة بما يشتت الانتباه.', 'fine_amount' => 30, 'severity_weight' => 3, 'is_active' => true],
            ['name' => 'عدم ارتداء الخوذة (دراجات)', 'description' => 'قيادة الدراجة النارية أو مرافقتها من دون خوذة واقية.', 'fine_amount' => 25, 'severity_weight' => 3, 'is_active' => true],
            ['name' => 'القيادة بدون رخصة', 'description' => 'قيادة المركبة دون امتلاك رخصة قيادة سارية.', 'fine_amount' => 100, 'severity_weight' => 5, 'is_active' => true],
            ['name' => 'انتهاء رخصة القيادة', 'description' => 'قيادة المركبة برخصة منتهية الصلاحية.', 'fine_amount' => 40, 'severity_weight' => 2, 'is_active' => true],
            ['name' => 'انتهاء ترخيص المركبة', 'description' => 'تشغيل مركبة بترخيص منتهي الصلاحية.', 'fine_amount' => 35, 'severity_weight' => 2, 'is_active' => true],
            ['name' => 'عدم وجود لوحات للمركبة', 'description' => 'سير المركبة من دون لوحات تعريف نظامية.', 'fine_amount' => 80, 'severity_weight' => 4, 'is_active' => true],
            ['name' => 'لوحات مزورة أو غير واضحة', 'description' => 'استخدام لوحات مزورة أو مطموسة أو غير قابلة للقراءة.', 'fine_amount' => 120, 'severity_weight' => 5, 'is_active' => true],
            ['name' => 'السير بعكس الاتجاه', 'description' => 'قيادة المركبة بعكس اتجاه السير المحدد للطريق.', 'fine_amount' => 120, 'severity_weight' => 5, 'is_active' => true],
            ['name' => 'القيادة تحت تأثير الكحول', 'description' => 'قيادة المركبة تحت تأثير الكحول بما يضعف القدرة على التحكم.', 'fine_amount' => 200, 'severity_weight' => 5, 'is_active' => true],
            ['name' => 'القيادة بتهور (تفحيط)', 'description' => 'القيام بحركات استعراضية أو قيادة متهورة تعرض الآخرين للخطر.', 'fine_amount' => 150, 'severity_weight' => 5, 'is_active' => true],
            ['name' => 'عدم إعطاء أولوية للمشاة', 'description' => 'عدم التوقف أو التمهل لإعطاء المشاة حق المرور.', 'fine_amount' => 50, 'severity_weight' => 3, 'is_active' => true],
            ['name' => 'تحميل ركاب أو حمولة زائدة', 'description' => 'تجاوز الحد المسموح للركاب أو الحمولة في المركبة.', 'fine_amount' => 40, 'severity_weight' => 3, 'is_active' => true],
            ['name' => 'إعاقة حركة السير', 'description' => 'التسبب بإرباك أو تعطيل انسيابية المرور بشكل مباشر.', 'fine_amount' => 30, 'severity_weight' => 2, 'is_active' => true],
            ['name' => 'عدم تشغيل الأضواء ليلاً', 'description' => 'قيادة المركبة ليلاً أو في ظروف الرؤية المنخفضة دون إضاءة مناسبة.', 'fine_amount' => 25, 'severity_weight' => 2, 'is_active' => true],
            ['name' => 'القيادة بمركبة غير صالحة فنياً', 'description' => 'تشغيل مركبة فيها عيوب فنية مؤثرة على السلامة العامة.', 'fine_amount' => 60, 'severity_weight' => 4, 'is_active' => true],
            ['name' => 'تجاوز الإشارة الصفراء بشكل خطر', 'description' => 'اجتياز الإشارة الصفراء بطريقة غير آمنة تعرض الآخرين للخطر.', 'fine_amount' => 45, 'severity_weight' => 3, 'is_active' => true],
            ['name' => 'تغيير المسار دون إشارة', 'description' => 'الانتقال بين المسارات دون استخدام الغماز أو تنبيه السائقين الآخرين.', 'fine_amount' => 35, 'severity_weight' => 3, 'is_active' => true],
            ['name' => 'القيادة بسرعة بطيئة تعيق السير', 'description' => 'السير بسرعة منخفضة بشكل غير مبرر يؤدي إلى تعطيل الحركة.', 'fine_amount' => 20, 'severity_weight' => 2, 'is_active' => true],
            ['name' => 'عدم الالتزام بالمسار المحدد', 'description' => 'عدم الالتزام بالمسار المروري المخصص لنوع المركبة أو لاتجاهها.', 'fine_amount' => 30, 'severity_weight' => 3, 'is_active' => true],
            ['name' => 'القيادة بدون تأمين', 'description' => 'تشغيل مركبة من دون وجود تأمين ساري المفعول.', 'fine_amount' => 90, 'severity_weight' => 4, 'is_active' => true],
            ['name' => 'استخدام منبه الصوت بشكل مزعج', 'description' => 'استخدام البوق بشكل مفرط أو مزعج خارج الحالات الضرورية.', 'fine_amount' => 15, 'severity_weight' => 1, 'is_active' => true],
            ['name' => 'رمي النفايات من المركبة', 'description' => 'إلقاء المخلفات من المركبة في الطريق العام أو الأرصفة.', 'fine_amount' => 20, 'severity_weight' => 1, 'is_active' => true],
            ['name' => 'القيادة في مناطق ممنوعة', 'description' => 'دخول أو قيادة المركبة في مناطق محظورة أو غير مصرح بها.', 'fine_amount' => 70, 'severity_weight' => 4, 'is_active' => true],
        ];

        ViolationType::query()
            ->whereNotIn('name', array_column($violationTypes, 'name'))
            ->delete();

        foreach ($violationTypes as $type) {
            ViolationType::query()->updateOrCreate(
                ['name' => $type['name']],
                $type
            );
        }
    }
}
