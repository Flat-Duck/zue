<?php

namespace App\Helpers;

use App\Models\User;

class Rules
{
    private static $letters ;
    private static $aDparments;
    private static $kbDepartments;
    private static $availableLetters;

    public static function getLetters(User $user)
    {
        self::initData();
        self::$availableLetters = static::$letters;
        if(!$user->isSuperAdmin())
        {
            // if(self::$aDparments->contains($user->employee->department->name)){
            //     self::$availableLetters = self::$letters->diff(['K', 'B']);
            // }elseif(self::$kbDepartments->contains($user->employee->department->name)){
            //     self::$availableLetters = static::$letters->diff(['A']);
            // }
        }
        return self::$availableLetters->all();
    }
    
    private static function initData()
    {
        self::$letters = collect(range('A', 'Z'));
        $excludeLetters = ['H', 'M', 'R', 'U', 'V'];
        self::$letters = self::$letters->diff($excludeLetters);
        self::$aDparments = collect(['MAINT', 'F. CO-R', 'TRANSP', 'ADMIN', 'F.COORD.', 'GEN MAINT', 'CAMP BOSS',
                            'ACCOUNTING', 'REDA PUMP', '', 'WAREHOUSE', 'CLINIC', 'LAB', 'TRAINING', 'H,S&E', 'F. ENG']);
        self::$kbDepartments = collect(['PROD' , 'Gas Plant' , 'DRILLING', 'SECURITY', 'PROD .NC163', 'F/S']);
    }

}
