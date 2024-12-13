<?php

namespace App\Exports;

use App\Models\City;
use App\Models\TrackList;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Calculation\TextData\Format;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class UsersExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithColumnFormatting
{

    use Importable;
    private $date;
    private $city;
    private $status;

    public function __construct(string|null $date, string $city, string $status)
    {
        $this->date = $date;
        $this->city = $city;
        $this->status = $status;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $query = TrackList::query()
            ->select('id', 'track_code', 'status', 'city');

        $dateColumn = 'to_client';

        if ($this->status !== 'Выберите статус') {
            switch ($this->status) {
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

        if ($this->date != null){
            $query->whereDate($dateColumn, $this->date);
        }
        if ($this->city != 'Выберите город'){
            $query->where('city', 'LIKE', $this->city);
        }

        if ($this->status !== 'Выберите статус'){
            $query->where('status', 'LIKE', $this->status);
        }

        return $query->with('user')->get();
    }

    /**
     * @param $data
     * @return array
     */
    public function map($data): array
    {
        return [
            $data->id,
            $data->track_code,
            $data->status,
            $data->city,
            $data->user->name ?? '',
            $data->user->login ?? '',
            $data->user->city ?? '',
        ];
    }
    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_NUMBER,
        ];
    }
    public function headings(): array
    {
        return [
            '#',
            'Трек код',
            'Статус',
            'Город',
            'Имя',
            'Телефон',
            'Город клиента',
        ];
    }
}
