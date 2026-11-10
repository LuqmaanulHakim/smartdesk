<?php

namespace Database\Seeders;

use App\Enums\TicketStatus;
use App\Jobs\ClassifyTicketJob;
use App\Models\Ticket;
use Illuminate\Database\Seeder;
use RuntimeException;

class TicketSeeder extends Seeder
{
    public function run(): void
    {
        $filePath = database_path('seeders/sample-tickets.csv');

        if (! file_exists($filePath)) {
            throw new RuntimeException('Sample tickets CSV file not found.');
        }

        $handle = fopen($filePath, 'r');

        if ($handle === false) {
            throw new RuntimeException('Unable to open sample tickets CSV file.');
        }

        try {
            $headers = fgetcsv($handle, 0, ',', '"', '');

            if ($headers === false) {
                throw new RuntimeException('Sample tickets CSV is empty.');
            }

            // Remove UTF-8 BOM if present.
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);

            $requiredHeaders = [
                'name',
                'email',
                'subject',
                'message',
                'status',
            ];

            if ($headers !== $requiredHeaders) {
                throw new RuntimeException('CSV headers do not match the expected format.');
            }

            $rowNumber = 0;
            $imported = 0;
            $skipped = 0;

            while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                if ($row === [null] || $row === []) {
                    continue;
                }

                $rowNumber++;

                if (count($row) !== count($headers)) {
                    throw new RuntimeException(
                        "Invalid CSV structure at data row {$rowNumber}."
                    );
                }

                $data = array_combine($headers, $row);

                $status = TicketStatus::tryFrom($data['status']);

                if ($status === null) {
                    throw new RuntimeException(
                        "Invalid ticket status at data row {$rowNumber}."
                    );
                }

                $ticket = Ticket::firstOrCreate(
                    [
                        'reference' => sprintf('SD-SAMPLE-%03d', $rowNumber),
                    ],
                    [
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'subject' => $data['subject'],
                        'message' => $data['message'],
                        'status' => $status,
                    ]
                );

                if ($ticket->wasRecentlyCreated) {
                    ClassifyTicketJob::dispatch($ticket);
                    $imported++;
                } else {
                    $skipped++;
                }
            }

            $this->command?->info("Imported {$imported} new sample tickets.");
            $this->command?->info("Skipped {$skipped} existing sample tickets.");
        } finally {
            fclose($handle);
        }
    }
}
