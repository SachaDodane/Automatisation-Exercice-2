<?php

namespace App\Console;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Office;
use Illuminate\Support\Facades\Schema;
use Slim\App;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PopulateDatabaseCommand extends Command
{
    private App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('db:populate');
        $this->setDescription('Populate database');
    }

    protected function execute(InputInterface $input, OutputInterface $output ): int
    {
        $output->writeln('Populate database...');

        /** @var \Illuminate\Database\Capsule\Manager $db */
        $db = $this->app->getContainer()->get('db');

        $db->getConnection()->statement("SET FOREIGN_KEY_CHECKS=0");
        $db->getConnection()->statement("TRUNCATE `employees`");
        $db->getConnection()->statement("TRUNCATE `offices`");
        $db->getConnection()->statement("TRUNCATE `companies`");
        $db->getConnection()->statement("SET FOREIGN_KEY_CHECKS=1");

        // Créer 2-4 sociétés
        $companies = [
            [
                'name' => 'TechInnovate Solutions',
                'phone' => '0601020304',
                'email' => 'contact@techinnovate.com',
                'website' => 'https://techinnovate.com',
                'image' => 'https://images.unsplash.com/photo-1497366216548-37526070297c',
            ],
            [
                'name' => 'DataFlow Systems',
                'phone' => '0602030405',
                'email' => 'info@dataflow.com',
                'website' => 'https://dataflow.com',
                'image' => 'https://images.unsplash.com/photo-1497366811353-6870744d04b2',
            ],
            [
                'name' => 'CloudNine Technologies',
                'phone' => '0603040506',
                'email' => 'hello@cloudnine.tech',
                'website' => 'https://cloudnine.tech',
                'image' => 'https://images.unsplash.com/photo-1497366754035-5f381699c2c5',
            ],
        ];

        $companyIds = [];
        foreach (array_slice($companies, 0, rand(2, 3)) as $company) {
            $db->table('companies')->insert([
                'name' => $company['name'],
                'phone' => $company['phone'],
                'email' => $company['email'],
                'website' => $company['website'],
                'image' => $company['image'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $companyIds[] = $db->getPdo()->lastInsertId();
        }

        // Créer 2-3 bureaux par société
        $cities = [
            ['name' => 'Paris', 'zip' => '75000'],
            ['name' => 'Lyon', 'zip' => '69000'],
            ['name' => 'Marseille', 'zip' => '13000'],
            ['name' => 'Bordeaux', 'zip' => '33000'],
            ['name' => 'Lille', 'zip' => '59000'],
            ['name' => 'Strasbourg', 'zip' => '67000'],
        ];

        $officeIds = [];
        foreach ($companyIds as $companyId) {
            $numOffices = rand(2, 3);
            $cityKeys = array_rand($cities, $numOffices);
            if (!is_array($cityKeys)) {
                $cityKeys = [$cityKeys];
            }

            foreach ($cityKeys as $cityKey) {
                $city = $cities[$cityKey];
                $db->table('offices')->insert([
                    'name' => "Bureau de {$city['name']}",
                    'address' => rand(1, 100) . " rue " . ['Principale', 'du Commerce', 'de la République', 'Victor Hugo'][rand(0, 3)],
                    'city' => $city['name'],
                    'zip_code' => $city['zip'],
                    'country' => 'France',
                    'email' => "bureau.{$city['name']}@" . explode('@', $companies[$companyId-1]['email'])[1],
                    'company_id' => $companyId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $officeIds[$companyId][] = $db->getPdo()->lastInsertId();
            }

            // Définir le siège social
            $headOfficeId = $officeIds[$companyId][0];
            $db->table('companies')
                ->where('id', $companyId)
                ->update(['head_office_id' => $headOfficeId]);
        }

        // Créer une dizaine d'employés par société
        $firstNames = ['Jean', 'Marie', 'Pierre', 'Sophie', 'Thomas', 'Julie', 'Nicolas', 'Emma', 'Lucas', 'Léa'];
        $lastNames = ['Martin', 'Bernard', 'Dubois', 'Thomas', 'Robert', 'Richard', 'Petit', 'Durand', 'Leroy', 'Moreau'];
        $positions = ['Développeur', 'Chef de projet', 'Designer', 'DevOps', 'Product Owner', 'Scrum Master', 'Architecte', 'DBA', 'QA Engineer', 'UX Designer'];

        foreach ($companyIds as $companyId) {
            $numEmployees = rand(8, 12);
            $companyOffices = $officeIds[$companyId];

            for ($i = 0; $i < $numEmployees; $i++) {
                $firstName = $firstNames[array_rand($firstNames)];
                $lastName = $lastNames[array_rand($lastNames)];
                $position = $positions[array_rand($positions)];
                $officeId = $companyOffices[array_rand($companyOffices)];
                $email = strtolower($firstName . '.' . $lastName . '@' . explode('@', $companies[$companyId-1]['email'])[1]);

                $db->table('employees')->insert([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'office_id' => $officeId,
                    'email' => $email,
                    'position' => $position,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $output->writeln('Database populated successfully!');
        return 0;
    }
}
