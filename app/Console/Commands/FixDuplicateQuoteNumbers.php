<?php

namespace App\Console\Commands;

use App\Models\Quote;
use Illuminate\Console\Command;

class FixDuplicateQuoteNumbers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quotes:fix-duplicates {--dry-run : Solo mostrar cambios sin aplicarlos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Corregir números de cotización duplicados';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $this->info($dryRun ? '🔍 MODO PRUEBA - No se aplicarán cambios' : '🔧 MODO REAL - Se aplicarán cambios');

        // Buscar números duplicados
        $duplicates = Quote::select('quote_number')
            ->groupBy('quote_number')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('quote_number');

        if ($duplicates->isEmpty()) {
            $this->info('✅ No se encontraron números de cotización duplicados.');
            return;
        }

        $this->warn("⚠️ Se encontraron " . $duplicates->count() . " números duplicados:");

        foreach ($duplicates as $duplicateNumber) {
            $quotes = Quote::where('quote_number', $duplicateNumber)
                ->orderBy('created_at', 'asc')
                ->get();

            $this->line("📋 Número duplicado: {$duplicateNumber}");
            $this->line("   Cantidad de registros: " . $quotes->count());

            // Mantener el primer registro y corregir los demás
            foreach ($quotes->skip(1) as $index => $quote) {
                $newNumber = Quote::generateQuoteNumber();

                $this->line("   🔄 Corrigiendo ID {$quote->id}: {$duplicateNumber} → {$newNumber}");

                if (!$dryRun) {
                    $quote->update(['quote_number' => $newNumber]);
                }
            }
        }

        if (!$dryRun) {
            $this->info('✅ Todos los números duplicados han sido corregidos.');
        } else {
            $this->info('ℹ️  Para aplicar los cambios, ejecuta sin la opción --dry-run');
        }
    }
}
