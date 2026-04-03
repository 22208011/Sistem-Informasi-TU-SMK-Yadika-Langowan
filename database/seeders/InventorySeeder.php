<?php

namespace Database\Seeders;

use App\Models\InventoryItem;
use App\Models\ItemCategory;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        // Create categories
        $categories = [
            ['code' => 'ELK', 'name' => 'Elektronik', 'description' => 'Peralatan elektronik dan komputer'],
            ['code' => 'FRN', 'name' => 'Furniture', 'description' => 'Meja, kursi, lemari, dan perabotan lainnya'],
            ['code' => 'ATK', 'name' => 'Alat Tulis Kantor', 'description' => 'ATK dan perlengkapan kantor'],
            ['code' => 'LAB', 'name' => 'Alat Laboratorium', 'description' => 'Peralatan praktikum lab'],
            ['code' => 'OLR', 'name' => 'Alat Olahraga', 'description' => 'Peralatan olahraga dan kebugaran'],
            ['code' => 'KBR', 'name' => 'Kebersihan', 'description' => 'Alat kebersihan dan sanitasi'],
            ['code' => 'LNY', 'name' => 'Lainnya', 'description' => 'Barang inventaris lainnya'],
        ];

        $categoryIds = [];
        foreach ($categories as $cat) {
            $category = ItemCategory::updateOrCreate(['code' => $cat['code']], $cat);
            $categoryIds[$cat['code']] = $category->id;
        }

        // Create sample inventory items
        $items = [
            // Elektronik
            [
                'category_id' => $categoryIds['ELK'],
                'code' => 'ELK-001',
                'name' => 'Komputer PC Desktop',
                'brand' => 'Dell',
                'model' => 'OptiPlex 3080',
                'quantity' => 30,
                'available_quantity' => 28,
                'condition' => 'baik',
                'location' => 'Lab Komputer 1',
                'purchase_date' => '2024-07-15',
                'purchase_price' => 8500000,
            ],
            [
                'category_id' => $categoryIds['ELK'],
                'code' => 'ELK-002',
                'name' => 'Proyektor',
                'brand' => 'Epson',
                'model' => 'EB-X51',
                'quantity' => 10,
                'available_quantity' => 9,
                'condition' => 'baik',
                'location' => 'Gudang TU',
                'purchase_date' => '2024-03-10',
                'purchase_price' => 6500000,
            ],
            [
                'category_id' => $categoryIds['ELK'],
                'code' => 'ELK-003',
                'name' => 'Printer',
                'brand' => 'HP',
                'model' => 'LaserJet Pro M404dn',
                'quantity' => 5,
                'available_quantity' => 5,
                'condition' => 'baik',
                'location' => 'Ruang TU',
                'purchase_date' => '2024-06-20',
                'purchase_price' => 4200000,
            ],
            [
                'category_id' => $categoryIds['ELK'],
                'code' => 'ELK-004',
                'name' => 'Laptop',
                'brand' => 'Lenovo',
                'model' => 'ThinkPad E14',
                'quantity' => 15,
                'available_quantity' => 12,
                'condition' => 'baik',
                'location' => 'Gudang TU',
                'purchase_date' => '2024-08-01',
                'purchase_price' => 9500000,
            ],
            // Furniture
            [
                'category_id' => $categoryIds['FRN'],
                'code' => 'FRN-001',
                'name' => 'Meja Siswa',
                'brand' => 'Olympic',
                'quantity' => 200,
                'available_quantity' => 200,
                'condition' => 'baik',
                'location' => 'Ruang Kelas',
                'purchase_date' => '2023-06-15',
                'purchase_price' => 450000,
            ],
            [
                'category_id' => $categoryIds['FRN'],
                'code' => 'FRN-002',
                'name' => 'Kursi Siswa',
                'brand' => 'Olympic',
                'quantity' => 200,
                'available_quantity' => 195,
                'condition' => 'baik',
                'location' => 'Ruang Kelas',
                'purchase_date' => '2023-06-15',
                'purchase_price' => 250000,
            ],
            [
                'category_id' => $categoryIds['FRN'],
                'code' => 'FRN-003',
                'name' => 'Whiteboard',
                'brand' => 'Sakana',
                'model' => '120x240cm',
                'quantity' => 20,
                'available_quantity' => 20,
                'condition' => 'baik',
                'location' => 'Ruang Kelas',
                'purchase_date' => '2024-01-10',
                'purchase_price' => 850000,
            ],
            // Lab
            [
                'category_id' => $categoryIds['LAB'],
                'code' => 'LAB-001',
                'name' => 'Mikroskop',
                'brand' => 'Olympus',
                'model' => 'CX21',
                'quantity' => 20,
                'available_quantity' => 18,
                'condition' => 'baik',
                'location' => 'Lab Biologi',
                'purchase_date' => '2024-02-20',
                'purchase_price' => 3500000,
            ],
            [
                'category_id' => $categoryIds['LAB'],
                'code' => 'LAB-002',
                'name' => 'Tabung Reaksi Set',
                'brand' => 'Pyrex',
                'quantity' => 10,
                'available_quantity' => 10,
                'condition' => 'baik',
                'location' => 'Lab Kimia',
                'purchase_date' => '2024-04-15',
                'purchase_price' => 250000,
            ],
            // Olahraga
            [
                'category_id' => $categoryIds['OLR'],
                'code' => 'OLR-001',
                'name' => 'Bola Basket',
                'brand' => 'Molten',
                'quantity' => 10,
                'available_quantity' => 8,
                'condition' => 'baik',
                'location' => 'Gudang Olahraga',
                'purchase_date' => '2024-05-10',
                'purchase_price' => 350000,
            ],
            [
                'category_id' => $categoryIds['OLR'],
                'code' => 'OLR-002',
                'name' => 'Bola Voli',
                'brand' => 'Mikasa',
                'quantity' => 8,
                'available_quantity' => 7,
                'condition' => 'baik',
                'location' => 'Gudang Olahraga',
                'purchase_date' => '2024-05-10',
                'purchase_price' => 280000,
            ],
            [
                'category_id' => $categoryIds['OLR'],
                'code' => 'OLR-003',
                'name' => 'Net Badminton',
                'brand' => 'Yonex',
                'quantity' => 4,
                'available_quantity' => 4,
                'condition' => 'baik',
                'location' => 'Gudang Olahraga',
                'purchase_date' => '2024-03-15',
                'purchase_price' => 450000,
            ],
        ];

        foreach ($items as $item) {
            InventoryItem::updateOrCreate(
                ['code' => $item['code']],
                array_merge($item, ['created_by' => 1])
            );
        }

        $this->command->info('Inventory seeder completed: '.count($categories).' categories, '.count($items).' items created.');
    }
}
