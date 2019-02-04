<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class CountryCurrency extends Model
{
    use Notifiable;

    use SoftDeletes;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'country_currency';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['country_name', 'country_code', 'calling_code', 'amount'];

    /**
     * The attributes that are dates
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * Insert and Update User
     */
    public function insertUpdate($data)
    {
        if (isset($data['id']) && $data['id'] != '' && $data['id'] > 0) {
            $countryCurrency = CountryCurrency::find($data['id']);
            $countryCurrency->update($data);
            return CountryCurrency::find($data['id']);
        } else {
            return CountryCurrency::create($data);
        }
    }

    public function getAllCountryData()
    {
        return CountryCurrency::get();
    }

    /**
     * To get all country code count
     * @param String $searchByName User Id
     * @param String $searchByCode User Id
     * @param String $searchByCallingCall User Id
     * @param String $searchByOrder User Id
     * @return CountryCurrency collection count
     */
    public static function getCountryCodeCount ($searchByName, $searchByCode, $searchByCallingCall, $searchByOrder) {
        $countryDataCount = CountryCurrency::whereRaw("1=1");

        // Search by country name
        if ($searchByName !== null) {
            $countryDataCount = $countryDataCount->where('country_name', 'LIKE', "%$searchByName%");
        }

        // Search by country code
        if ($searchByCode !== null) {
            $countryDataCount = $countryDataCount->where('country_code', 'LIKE', "%$searchByCode%");
        }

        // Search by calling code
        if ($searchByCallingCall !== null) {
            $countryDataCount = $countryDataCount->where('calling_code', 'LIKE', "%$searchByCallingCall%");
        }

        // Search by sort order
        if ($searchByOrder !== null) {
            $countryDataCount = $countryDataCount->where('sort_order', 'LIKE', "%$searchByOrder%");
        }
        
        $countryDataCount = $countryDataCount->count();
        return $countryDataCount;
    }

    /**
     * To get all country code data
     * @param Integer $limit User Id
     * @param Integer $offset User Id
     * @param String $sort User Id
     * @param String $order User Id
     * @param String $searchByName User Id
     * @param String $searchByCode User Id
     * @param String $searchByCallingCall User Id
     * @param String $searchByOrder User Id
     * @return CountryCurrency collection
     */
    public static function getListing($limit, $offset, $sort, $order, $searchByName, $searchByCode, $searchByCallingCall, $searchByOrder)
    {
        $countryData = CountryCurrency::whereRaw("1=1");

        // Search by country name
        if ($searchByName !== null) {
            $countryData = $countryData->where('country_name', 'LIKE', "%$searchByName%");
        }

        // Search by country code
        if ($searchByCode !== null) {
            $countryData = $countryData->where('country_code', 'LIKE', "%$searchByCode%");
        }

        // Search by calling code
        if ($searchByCallingCall !== null) {
            $countryData = $countryData->where('calling_code', 'LIKE', "%$searchByCallingCall%");
        }

        // Search by sort order
        if ($searchByOrder !== null) {
            $countryData = $countryData->where('sort_order', 'LIKE', "%$searchByOrder%");
        }

        $countryData = $countryData->orderBy($sort, $order)
            ->orderBy('id', 'ASC')
            ->take($limit)
            ->offset($offset)
            ->get([
                'id',
                'country_name',
                'country_code',
                'calling_code',
                'sort_order',
            ]);
        return $countryData;
    }

}
