<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /**
	     * Add Roles
	     *
		 */
        if (Role::where('slug', '=', 'student')->first() === null) {
	        $role = Role::create([
	            'name' => 'Student',
	            'slug' => 'student',
        	]);
	    }
		if (Role::where('slug', '=', 'teacher')->first() === null) {
	        $role = Role::create([
	            'name' => 'Teacher',
	            'slug' => 'teacher',
        	]);
	    }		
    }
}
