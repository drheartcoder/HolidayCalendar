<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Holiday;

class FetchHolidays extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:holidays';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch holidays from Calendarific API';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $apiKey = env('CALENDARIFIC_API_KEY');
        Log::info('Using API Key:', ['apiKey' => $apiKey]);

        $country = 'US';
        $year = now()->year;

        $response = Http::get("https://calendarific.com/api/v2/holidays?&api_key={$apiKey}&country={$country}&year={$year}");

        Log::info('API Request', [
            'url' => "https://calendarific.com/api/v2/holidays?&api_key={$apiKey}&country={$country}&year={$year}",
            'status' => $response->status(),
            'response' => $response->body()
        ]);

        if ($response->successful()) {
            $holidays = $response->json()['response']['holidays'];

            foreach ($holidays as $holiday) {
                // Extract the date part from the ISO 8601 date string
                $date = substr($holiday['date']['iso'], 0, 10);

                Holiday::updateOrCreate(
                    ['name' => $holiday['name'], 'date' => $date],
                    ['type' => $holiday['type'][0]]
                );
            }

            $this->info('Holidays fetched successfully.');
        } else {
            $this->error('Failed to fetch holidays.');
            $this->error('Status Code: ' . $response->status());
            $this->error('Response: ' . $response->body());
        }
    }
}
