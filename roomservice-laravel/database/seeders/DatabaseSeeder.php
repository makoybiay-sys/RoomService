<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\StaffAccount;
use App\Models\MenuItem;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Gumawa ng Staff Accounts (Default password: password123)
        $staff = [
            ['employee_id' => 'ADMIN-001', 'username' => 'admin', 'full_name' => 'System Administrator', 'role' => 'ADMIN'],
            ['employee_id' => 'FNB-001', 'username' => 'fnb_manager', 'full_name' => 'Maria Santos', 'role' => 'FNB_MANAGER'],
            ['employee_id' => 'FRONT-001', 'username' => 'frontdesk', 'full_name' => 'Juan Dela Cruz', 'role' => 'FRONT_DESK'],
            ['employee_id' => 'COFFEE-001', 'username' => 'coffeeclub', 'full_name' => 'Pedro Reyes', 'role' => 'COFFEE_MANAGER', 'area' => 'COFFEE_CLUB'],
            ['employee_id' => 'WAITER-001', 'username' => 'waiter1', 'full_name' => 'Waiter Jose Ramos', 'role' => 'WAITER'],
            ['employee_id' => 'CHEF-001', 'username' => 'kitchen', 'full_name' => 'Chef Boy Logro', 'role' => 'KITCHEN'],
        ];

        foreach ($staff as $s) {
            StaffAccount::create(array_merge($s, [
                'password' => Hash::make('password123'),
                'is_active' => true
            ]));
        }

        // 2. Gumawa ng Menu Items
        $menu = [
            ['name' => 'Burger Meal', 'description' => 'Beef patty with cheese & fries', 'category' => 'Main Course', 'price' => 199.00, 'daily_stock' => 20, 'remaining_stock' => 20],
            ['name' => 'Chicken Pizza', 'description' => 'Wood-fired chicken pizza', 'category' => 'Main Course', 'price' => 299.00, 'daily_stock' => 10, 'remaining_stock' => 10],
            ['name' => 'Carbonara', 'description' => 'Creamy pasta carbonara', 'category' => 'Main Course', 'price' => 179.00, 'daily_stock' => 20, 'remaining_stock' => 20],
            ['name' => 'Mixed Garden Salad', 'description' => 'Fresh greens with dressing', 'category' => 'Appetizer', 'price' => 129.00, 'daily_stock' => 15, 'remaining_stock' => 15],
            ['name' => 'Halo-Halo', 'description' => 'Filipino shaved ice dessert', 'category' => 'Dessert', 'price' => 99.00, 'daily_stock' => 30, 'remaining_stock' => 30],
            ['name' => 'Iced Tea', 'description' => 'Chilled house blend', 'category' => 'Beverage', 'price' => 59.00, 'daily_stock' => 50, 'remaining_stock' => 50],
            ['name' => 'Brewed Coffee', 'description' => 'Hot freshly brewed coffee', 'category' => 'Beverage', 'price' => 69.00, 'daily_stock' => 40, 'remaining_stock' => 40],
        ];

        foreach ($menu as $m) {
            MenuItem::create(array_merge($m, ['is_available' => true]));
        }
    }
}