<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Commission;

class CommissionStartRange implements Rule
{
    private $errorMessage = [];

    private $lastErrorType = 'range_incremental';

    private $endRangeInput = 'end_range';

    private $id = false;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($endRangeField, $recordId = 0)
    {
        $this->endRangeInput = $endRangeField;
        $this->id = $recordId;

        //
        $this->errorMessage = [
            'range_with_plus' => trans("validation.range_with_plus"),
            'range_incremental' => trans("validation.range_incremental"),
            'invalid_range' => trans("validation.invalid_range"),
        ];

    }

    /**
     * Validation rule will validate the start and end range to the passed values against database values.
     * 
     * Rules will check for exclusive and incremental range values.
     * #Rule 1: Check for start values this should be higher than stored values in database.
     * #Rule 2: Privously stored values is not range than prompt user to edit previous value to change it range value.
     * #Rule 3: Find out available Range values from the stored values and verify passed start and end value.
     * #Rule 4: When passed values in edited version than allow to edit those values along with above 3 rules.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $value = number_format($value, 2);
        $endRangeValue = \Request::get($this->endRangeInput);

        $isPlusSign = Commission::where("amount_range", "like", "%+")->count();
        if ($isPlusSign > 0) {
            $this->lastErrorType = 'range_with_plus';
            return false;
        }

        $rCount = Commission::count();
        $editableRange = null;
        if ($rCount > 0) {
            /**
             * Passed range is new commission record so need to validate against all database values.
             */
            if ($this->id == 0) {
                $savedRange = Commission::select("start_range", "end_range", "amount_range")
                    ->orderBy("start_range")
                    ->orderBy("end_range")
                    ->get()
                    ->toArray();
            } else {
                /**
                 * Passed range is already exist record so need to exclude that record.
                 */
                $savedRange = Commission::select("start_range", "end_range", "amount_range")
                    ->where("id", '<>', $this->id)
                    ->orderBy("start_range")
                    ->orderBy("end_range")
                    ->get()
                    ->toArray();
                /**
                 * Get the record which is going to edited, we need this slot to set as available slot so it can be allow to edit 
                 * range in between.
                 */
                $editableRange = Commission::select("start_range", "end_range", "amount_range")
                    ->where("id", '=', $this->id)
                    ->orderBy("start_range")
                    ->orderBy("end_range")
                    ->first();

            }
            $rCount = count($savedRange);
            $availableSlot = [];

            if ($editableRange != null) {
                /**
                 * Allow user to edit range between prevously available range.
                 */
                $availableSlot[] = [
                    "available" => "Yes",
                    "start_range" => number_format(floatval($editableRange->start_range), 2) - 0.01,
                    "end_range" => number_format(floatval($editableRange->end_range), 2) + 0.01
                ];
            }

            /**
             * Finding all available range slot which can be add/edit by user.
             */
            for ($rIndex = 0; $rIndex < ($rCount - 1); $rIndex++) {
                $start = number_format(floatval($savedRange[$rIndex]["start_range"]), 2);
                $end = number_format(floatval($savedRange[$rIndex]["end_range"]), 2);
                $new_start = number_format(floatval($savedRange[$rIndex]["end_range"]), 2) + 0.01;

                if ($new_start != $savedRange[$rIndex + 1]["start_range"]) {
                    $availableSlot[] = [
                        "available" => "Yes",
                        "start_range" => $new_start,
                        "end_range" => number_format(floatval($savedRange[$rIndex + 1]["start_range"]), 2) - 0.01
                    ];
                }
                if ($rIndex == ($rCount - 1)) {
                    $availableSlot[] = ["available" => "Yes",
                                        "start_range" => number_format(floatval($savedRange[$rIndex + 1]["end_range"]), 2) - 0.01,
                                        "end_range" => number_format(floatval(100), 2)];        
                }
            }

            if($rCount > 1) {
                $end_range = floatval($savedRange[$rCount-1]["end_range"]) + 0.01;
                $availableSlot[] = [
                    "available" => "Yes",
                    "start_range" => number_format($end_range, 2),
                    "end_range" => "+"
                ];
            }
            $availableSlotCount = count($availableSlot);
            $isValidRange = 0;
            if ($availableSlotCount > 0) {
                /**
                 * Comparing available range slot with passed range.
                 */
                for ($rIndex = 0; $rIndex < $availableSlotCount; $rIndex++) {
                    if (isset($endRangeValue)) {
                        if ($availableSlot[$rIndex]["end_range"] != "+") {
                            if ($availableSlot[$rIndex]["start_range"] >= $value && $availableSlot[$rIndex]["end_range"] <= $endRangeValue) {
                                $isValidRange++;
                            }
                            // if ($value >= $availableSlot[$rIndex]["start_range"] && $endRangeValue <= $availableSlot[$rIndex]["end_range"]) {
                            //     $isValidRange++;
                            // }
                        } else {
                            if ($availableSlot[$rIndex]["start_range"] >= $value && ($availableSlot[$rIndex]["end_range"] == "+")) {
                                $isValidRange++;
                            }
                        }
                    } else {
                        if ($availableSlot[$rIndex]["end_range"] == "+") {
                            if ($availableSlot[$rIndex]["start_range"] >= $value) {
                                $isValidRange++;
                            }
                        } else {
                            if ($availableSlot[$rIndex]["start_range"] >= $value && ($availableSlot[$rIndex]["end_range"] == "" || $availableSlot[$rIndex]["end_range"] == null)) {
                                $isValidRange++;
                            }
                        }
                    }
                }
                // echo "$isValidRange";
                // dd($availableSlot);
                if ($isValidRange == 0) {
                    $this->lastErrorType = 'invalid_range';
                    return false;
                }
            } else {
                $max_start_range = Commission::max("start_range");
                $max_end_range = Commission::max("end_range");

                $maxRange = number_format(max($max_start_range, $max_end_range), 2);
                if (($value > $maxRange) === false) {
                    $this->lastErrorType = 'range_incremental';
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return $this->errorMessage[$this->lastErrorType];
        //return 'The validation error message.';
    }
}