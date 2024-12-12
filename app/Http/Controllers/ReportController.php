<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Configuration;
use App\Models\TrackList;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function getTrackReportPage(){
        $cities = City::query()->select('title')->get();
        $config = Configuration::query()->select('title_text')->first();
        $city = '';
        $date = '';
        $status = '';
        return view('report', compact('cities', 'config', 'city', 'date', 'status'));
    }

    public function getTrackReport(Request $request){

        try {
            $city = '';
            $date = '';
            $status = '';
            $dateColumn = 'to_client'; // значение по умолчанию

            // Определяем колонку для даты в зависимости от статуса
            if ($request->status != 'Выберите статус') {
                switch ($request->status) {
                    case 'Отправлено в Ваш город':
                    case 'Выдано клиенту':
                        $dateColumn = 'to_client';
                        break;
                    case 'Товар принят':
                        $dateColumn = 'client_accept';
                        break;
                    case 'Получено на складе в Алматы':
                        $dateColumn = 'to_almaty';
                        break;
                    case 'Получено в Китае':
                        $dateColumn = 'to_china';
                        break;
                }
            }
            $query = TrackList::query()
                ->select('track_code', 'status', 'city');

            $recordCount = $query->count();
            if ($recordCount > 10000) { // установите свой лимит
                throw new \RuntimeException('Слишком много записей для отображения');
            }

            if ($request->date != null){
                $query->whereDate($dateColumn, $request->date);
                $date = $request->date;
            }
            if ($request->city != 'Выберите город'){
                $query->where('city', 'LIKE', $request->city);
                $city = $request->city;
            }
            if ($request->status != 'Выберите статус'){
                $query->where('status', 'LIKE', $request->status);
                $status = $request->status;
            }
            $cities = City::query()->select('title')->get();
            $tracks = $query->with('user')->get();
            $count = $tracks->count();
            $config = Configuration::query()->select('title_text')->first();


            return view('report', compact('tracks', 'cities', 'config', 'city', 'date', 'status', 'count'));
        } catch (\Throwable $e) {
            \Log::error('Report generation error: ' . $e->getMessage());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'error' => 'Выбран слишком большой объем данных. Пожалуйста, уточните параметры фильтрации.'
                ], 500);
            }

            return response()->view('report', [
                'error' => 'Выбран слишком большой объем данных. Пожалуйста, уточните параметры фильтрации.'
            ], 500);
        }
    }
}
