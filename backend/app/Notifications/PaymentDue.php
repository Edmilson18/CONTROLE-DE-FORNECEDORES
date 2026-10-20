<?php

namespace App\Notifications;

use App\Models\Compra;
use Illuminate\Notifications\Notification;

class PaymentDue extends Notification
{
    public function __construct(public Compra $purchase) {}
    public function via(object $notifiable): array { return ['database']; }
    public function toArray(object $notifiable): array
    {
        return ['compra_id' => $this->purchase->id,
            'titulo' => 'Pagamento pendente · Compra #'.$this->purchase->id,
            'mensagem' => 'Vencimento em '.$this->purchase->vencimento->format('d/m/Y').'. Confira o saldo na compra.',
            'vencimento' => $this->purchase->vencimento->toDateString()];
    }
}
