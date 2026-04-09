<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class UsersImport implements ToModel, WithHeadingRow, WithValidation
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        // Simple password generation: phone without leading 0
        $phone = (string) ($row['phone'] ?? '');
        $passwordPlain = ltrim($phone, '0');
        
        // Fallback if phone is empty
        if (empty($passwordPlain)) {
            $passwordPlain = '12345678'; // Default fallback
        }

        return new User([
            'name'     => $row['name'],
            'email'    => $row['email'],
            'number'   => (int) $row['number'],
            'password' => Hash::make($passwordPlain),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'number' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('users', 'number'),
                Rule::unique('users', 'id'),
            ],
        ];
    }
}
