@extends('layouts.app')

@section('content')
<div x-data="pensionForm(@js(old('commutation', '35')))">
    <form method="POST" action="{{ route('pension.calculate') }}" class="space-y-4" @submit="logSubmit">
        @csrf

        @if ($errors->any())
            <div class="rounded-md border border-red-500/40 bg-red-500/10 px-4 py-3 text-sm text-red-600" role="alert">
                <p class="mb-2 font-semibold">{{ __('app.form_errors_title') }}</p>
                <ul class="list-disc space-y-1 ps-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mb-6">
            <h3 class="mb-4 text-base font-semibold">{{ __('app.personal_details') }}</h3>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 flex items-center text-sm">
                    <span>{{ __('app.name') }}</span>
                    <x-field-help
                        enTitle="Name"
                        enText="Enter full official name as it appears on service records."
                        urTitle="نام"
                        urText="اپنا پورا سرکاری نام درج کریں جیسا کہ سروس ریکارڈ میں موجود ہے۔"
                    />
                </label>
                <input class="input-core w-full rounded-md px-3 py-2" name="name" value="{{ old('name') }}" required>
                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 flex items-center text-sm">
                    <span>{{ __('app.designation') }}</span>
                    <x-field-help
                        enTitle="Designation"
                        enText="Enter your last held designation. This helps contextualize the pension record."
                        urTitle="عہدہ"
                        urText="اپنا آخری عہدہ درج کریں۔ یہ پنشن ریکارڈ کی وضاحت میں مدد دیتا ہے۔"
                    />
                </label>
                <input class="input-core w-full rounded-md px-3 py-2" name="designation" value="{{ old('designation') }}">
            </div>
            <div>
                <label class="mb-1 flex items-center text-sm">
                    <span>{{ __('app.bps') }}</span>
                    <x-field-help
                        enTitle="BPS"
                        enText="Select your grade from 1 to 22. It affects medical allowance calculation."
                        urTitle="بی پی ایس"
                        urText="اپنا گریڈ 1 سے 22 تک منتخب کریں۔ اس سے میڈیکل الاؤنس کی شرح متاثر ہوتی ہے۔"
                    />
                </label>
                <div class="stepper-wrap" x-data="{ val: '{{ old('bps', 1) }}', min: 1, max: 22, step: 1 }">
                    <input
                        class="input-core stepper-input w-full rounded-md px-3 py-2 pr-24"
                        type="number"
                        x-model="val"
                        name="bps"
                        min="1"
                        max="22"
                        step="1"
                        required
                    >
                    <x-stepper-controls
                        down-action="val = String(Math.max(min, (parseFloat(val || min) - step)))"
                        up-action="val = String(Math.min(max, (parseFloat(val || min) + step)))"
                    />
                </div>
                @error('bps') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 flex items-center text-sm">
                    <span>{{ __('app.pension_type') }}</span>
                    <x-field-help
                        enTitle="Pension Type"
                        enText="Choose the pension category that applies to your retirement case."
                        urTitle="پنشن کی قسم"
                        urText="وہ پنشن کیٹیگری منتخب کریں جو آپ کے ریٹائرمنٹ کیس پر لاگو ہوتی ہے۔"
                    />
                </label>
                <select class="input-core w-full rounded-md px-3 py-2" name="pension_type" required>
                    <option value="superannuation" {{ old('pension_type', 'superannuation') === 'superannuation' ? 'selected' : '' }}>{{ __('app.superannuation') }}</option>
                    <option value="retiring" {{ old('pension_type') === 'retiring' ? 'selected' : '' }}>{{ __('app.retiring') }}</option>
                    <option value="death_during_service" {{ old('pension_type') === 'death_during_service' ? 'selected' : '' }}>{{ __('app.death_during_service') }}</option>
                    <option value="voluntary" {{ old('pension_type') === 'voluntary' ? 'selected' : '' }}>{{ __('app.voluntary') }}</option>
                </select>
            </div>
            <div>
                <label class="mb-1 flex items-center text-sm">
                    <span>{{ __('app.date_of_birth') }}</span>
                    <x-field-help
                        enTitle="Date of Birth"
                        enText="Provide date of birth to determine age factor for commutation."
                        urTitle="تاریخ پیدائش"
                        urText="عمر فیکٹر (کمیوٹیشن) نکالنے کے لیے تاریخ پیدائش درج کریں۔"
                    />
                </label>
                <x-date-input name="dob" />
                @error('dob') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 flex items-center text-sm">
                    <span>{{ __('app.date_of_joining') }}</span>
                    <x-field-help
                        enTitle="Date of Joining"
                        enText="Enter your first date of service. It is used for qualifying service years."
                        urTitle="تاریخ تقرری"
                        urText="سروس شروع ہونے کی پہلی تاریخ درج کریں۔ اس سے قابلِ شمار سروس نکالی جاتی ہے۔"
                    />
                </label>
                <x-date-input name="date_of_joining" />
                @error('date_of_joining') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 flex items-center text-sm">
                    <span>{{ __('app.date_of_retirement') }}</span>
                    <x-field-help
                        enTitle="Date of Retirement"
                        enText="Enter your planned or actual retirement date (past or future). Annual increases are applied after that year."
                        urTitle="تاریخ ریٹائرمنٹ"
                        urText="اپنی منصوبہ بند یا اصل ریٹائرمنٹ تاریخ (ماضی یا مستقبل) درج کریں۔ سالانہ اضافے اس سال کے بعد لاگو ہوتے ہیں۔"
                    />
                </label>
                <x-date-input name="date_of_retirement" />
                @error('date_of_retirement') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            </div>
        </div>

        <div class="mb-6">
            <h3 class="mb-4 text-base font-semibold">{{ __('app.pensionable_emoluments') }}</h3>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 flex items-center text-sm">
                    <span>{{ __('app.basic_pay') }}</span>
                    <x-field-help
                        enTitle="Basic / Average Pay"
                        enText="Enter the latest basic or average monthly pay at retirement."
                        urTitle="بنیادی / اوسط تنخواہ"
                        urText="ریٹائرمنٹ کے وقت کی تازہ ترین ماہانہ بنیادی یا اوسط تنخواہ درج کریں۔"
                    />
                </label>
                <div class="stepper-wrap" x-data="{ val: '{{ old('basic_pay', 1000) }}', min: 1000, step: 100 }">
                    <input class="input-core stepper-input w-full rounded-md px-3 py-2 pr-24" type="number" step="0.01" min="1000" x-model="val" name="basic_pay" required>
                    <x-stepper-controls
                        down-action="val = Math.max(min, (parseFloat(val || min) - step)).toFixed(2)"
                        up-action="val = (parseFloat(val || min) + step).toFixed(2)"
                    />
                </div>
            </div>
            <div>
                <label class="mb-1 flex items-center text-sm">
                    <span>{{ __('app.special_pay') }}</span>
                    <x-field-help
                        enTitle="Special Pay"
                        enText="Include approved special pay, if applicable; otherwise keep 0."
                        urTitle="اسپیشل پے"
                        urText="اگر منظور شدہ اسپیشل پے ہو تو درج کریں، ورنہ 0 رہنے دیں۔"
                    />
                </label>
                <div class="stepper-wrap" x-data="{ val: '{{ old('special_pay', 0) }}', min: 0, step: 100 }">
                    <input class="input-core stepper-input w-full rounded-md px-3 py-2 pr-24" type="number" step="0.01" min="0" x-model="val" name="special_pay">
                    <x-stepper-controls
                        down-action="val = Math.max(min, (parseFloat(val || min) - step)).toFixed(2)"
                        up-action="val = (parseFloat(val || min) + step).toFixed(2)"
                    />
                </div>
            </div>
            <div>
                <label class="mb-1 flex items-center text-sm">
                    <span>{{ __('app.personal_pay') }}</span>
                    <x-field-help
                        enTitle="Personal Pay"
                        enText="Enter personal pay amount if sanctioned in your case."
                        urTitle="پرسنل پے"
                        urText="اگر آپ کے کیس میں پرسنل پے منظور شدہ ہے تو رقم درج کریں۔"
                    />
                </label>
                <div class="stepper-wrap" x-data="{ val: '{{ old('personal_pay', 0) }}', min: 0, step: 100 }">
                    <input class="input-core stepper-input w-full rounded-md px-3 py-2 pr-24" type="number" step="0.01" min="0" x-model="val" name="personal_pay">
                    <x-stepper-controls
                        down-action="val = Math.max(min, (parseFloat(val || min) - step)).toFixed(2)"
                        up-action="val = (parseFloat(val || min) + step).toFixed(2)"
                    />
                </div>
            </div>
            <div>
                <label class="mb-1 flex items-center text-sm">
                    <span>{{ __('app.qualification_pay') }}</span>
                    <x-field-help
                        enTitle="Qualification Pay"
                        enText="Enter qualification pay if admissible under your department rules."
                        urTitle="کوالیفکیشن پے"
                        urText="اگر محکمانہ قواعد کے تحت قابلِ ادائیگی ہو تو کوالیفکیشن پے درج کریں۔"
                    />
                </label>
                <div class="stepper-wrap" x-data="{ val: '{{ old('qualification_pay', 0) }}', min: 0, step: 100 }">
                    <input class="input-core stepper-input w-full rounded-md px-3 py-2 pr-24" type="number" step="0.01" min="0" x-model="val" name="qualification_pay">
                    <x-stepper-controls
                        down-action="val = Math.max(min, (parseFloat(val || min) - step)).toFixed(2)"
                        up-action="val = (parseFloat(val || min) + step).toFixed(2)"
                    />
                </div>
            </div>
            <div>
                <label class="mb-1 flex items-center text-sm">
                    <span>{{ __('app.retiring_increment') }}</span>
                    <x-field-help
                        enTitle="Retiring Increment"
                        enText="Enter the retiring increment percentage to add to pensionable emoluments (e.g. 5 for 5%). Leave 0 if not applicable."
                        urTitle="ریٹائرنگ انکریمنٹ"
                        urText="قابلِ پنشن تنخواہ میں شامل کرنے کے لیے ریٹائرنگ انکریمنٹ فیصد درج کریں (مثلاً 5 کا مطلب 5%)۔ اگر لاگو نہ ہو تو 0 رکھیں۔"
                    />
                </label>
                <div class="stepper-wrap" x-data="{ val: '{{ old('retiring_increment', 0) }}', min: 0, max: 100, step: 0.5 }">
                    <input
                        class="input-core stepper-input w-full rounded-md px-3 py-2 pr-24"
                        type="number"
                        step="0.01"
                        min="0"
                        max="100"
                        x-model="val"
                        name="retiring_increment"
                        placeholder="0"
                    >
                    <x-stepper-controls
                        down-action="val = Math.max(min, (parseFloat(val || min) - step)).toFixed(2)"
                        up-action="val = Math.min(max, (parseFloat(val || min) + step)).toFixed(2)"
                    />
                </div>
                <p class="mt-1 text-xs opacity-75">{{ __('app.retiring_increment_hint') }}</p>
                @error('retiring_increment') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            </div>
        </div>

        <div class="mb-6">
            <h3 class="mb-4 text-base font-semibold">{{ __('app.commutation_section') }}</h3>
            <div>
                <label class="mb-1 flex items-center text-sm">
                    <span>{{ __('app.commutation_percentage') }}: <span x-text="commutation"></span>%</span>
                    <x-field-help
                        enTitle="Commutation Percentage"
                        enText="Choose commutation from 0% to 35%. Higher commutation increases lump sum but lowers monthly net pension."
                        urTitle="کمیوٹیشن فیصد"
                        urText="0% سے 35% تک کمیوٹیشن منتخب کریں۔ زیادہ کمیوٹیشن یکمشت رقم بڑھاتی ہے مگر ماہانہ نیٹ پنشن کم کرتی ہے۔"
                    />
                </label>
                <input class="theme-range w-full" x-model="commutation" type="range" name="commutation" min="0" max="35" step="0.5" value="{{ old('commutation', 35) }}">
            </div>
        </div>

        <div class="mb-6">
            <h3 class="mb-4 text-base font-semibold">{{ __('app.government_types') }}</h3>
            <div class="flex flex-col gap-3 sm:flex-row sm:gap-8">
                <label class="government-radio-label flex cursor-pointer items-center gap-2 text-sm">
                    <input
                        type="radio"
                        name="government_type"
                        value="federal"
                        class="government-radio"
                        {{ old('government_type', 'federal') === 'federal' ? 'checked' : '' }}
                        required
                    >
                    <span>{{ __('app.federal_government') }}</span>
                </label>
                <label class="government-radio-label flex cursor-pointer items-center gap-2 text-sm">
                    <input
                        type="radio"
                        name="government_type"
                        value="khyber_pakhtunkhwa"
                        class="government-radio"
                        {{ old('government_type') === 'khyber_pakhtunkhwa' ? 'checked' : '' }}
                        required
                    >
                    <span>{{ __('app.khyber_pakhtunkhwa') }}</span>
                </label>
            </div>
            @error('government_type') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="btn-primary rounded-md px-4 py-2" @click="console.log('[Pension] Calculate Pension button clicked')">
            {{ __('app.calculate_pension') }}
        </button>
    </form>
</div>
@endsection
