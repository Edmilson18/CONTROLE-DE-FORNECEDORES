<?php

namespace App\Console\Commands;

use App\Models\Compra;
use App\Models\User;
use App\Notifications\PaymentDue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NotifyPayments extends Command
{
    protected $signature = 'pagamentos:notificar';
    protected $description = 'Notifica usuários sobre compras vencidas ou com vencimento nos próximos três dias';

    public function handle(): int
    {
        $count = 0;
        $users = User::where('ativo', true)->get();
        Compra::with('pagamentos')->where('status', 'pendente')->whereDate('vencimento', '<=', today()->addDays(3))->chunkById(100, function ($purchases) use ($users, &$count): void {
            foreach ($purchases as $purchase) {
                if ($purchase->balanceCents() === 0) {
                    continue;
                }
                foreach ($users as $user) {
                    DB::transaction(function () use ($user, $purchase, &$count): void {
                        $inserted = DB::table('notification_deliveries')->insertOrIgnore([
                            'user_id' => $user->id, 'compra_id' => $purchase->id, 'dia' => today()->toDateString(),
                        ]);
                        if ($inserted) {
                            $user->notify(new PaymentDue($purchase));
                            $count++;
                        }
                    });
                }
            }
        });
        $this->info("Notificações enviadas: {$count}");
        return self::SUCCESS;
    }
}
