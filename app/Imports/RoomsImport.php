<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\Residence;
use App\Models\Room;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Str;

class RoomsImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row)
        {
            if (isset($row[0]) && isset($row[1]) && isset($row[2]) && isset($row[3]))
            {
                $room =   Room::firstOrCreate([
                    'number'    => $row[3],
                    'residence_id' => $this->getResident($row[2],$row[1])],
                    [
                    'number'    => $row[3],
                    'residence_id' => $this->getResident($row[2],$row[1]),
                    'beds'    => 1
                ]);
            }
            
            if(Employee::find($row[0]) && !Str::contains($row[0],'+'))
            {
                $room->employees()->attach($row[0],['is_owner' => true, 'is_here' => true]);
            }
        }
    }
    
    private function getResident($name, $type)
    {
        $re =  Residence::firstOrCreate(
            [
                'name'    => $name,
                'type'    => $type
            ],[
                'name'    => $name,
                'type'    => $type
            ]
        );
        
        return $re->id;
    }
}
