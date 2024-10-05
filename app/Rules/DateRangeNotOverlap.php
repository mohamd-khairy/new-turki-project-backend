<?php

namespace App\Rules;

use App\Models\WelcomeMoney;
use Illuminate\Contracts\Validation\Rule;

class DateRangeNotOverlap implements Rule
{
    protected $startDate;
    protected $endDate;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        // Query the database to check if there is any overlap
        $overlap = WelcomeMoney::when(request()->get('id'), function ($query) {
            $query->where('id', '!=', request()->get('id'));
        })->where(function ($query) {
            $query->where('welcome_start_date', '<=', $this->endDate)
                ->where('welcome_end_date', '>=', $this->startDate);
        })->where('country_id', request()->get('country_id'))->exists();

        return !$overlap;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return "الفترة الزمنية المحددة تتداخل مع فترة موجودة مسبقًا.";
    }
}
