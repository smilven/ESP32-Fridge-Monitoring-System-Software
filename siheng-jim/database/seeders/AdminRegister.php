<?php



namespace Database\Seeders;



use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use Illuminate\Database\Seeder;

use App\Models\User;

use Hash;

class AdminRegister extends Seeder

{

    /**

     * Run the database seeds.

     */

    public function run(): void

    {

        User::create([

            "name"=>"Admin",

            "email"=>"chillermonitoradmin@marrybrown.com",

            "password"=>Hash::make('sihengjim0322##'),
        ]);

    }

}