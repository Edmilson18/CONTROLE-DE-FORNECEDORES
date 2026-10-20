<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fornecedores')) {
            Schema::create('fornecedores', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('nome', 200)->index();
                $table->string('cnpj', 18)->index();
                $table->string('telefone', 20);
                $table->string('cidade', 200);
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('compras')) {
            Schema::create('compras', function (Blueprint $table): void {
                $table->increments('id');
                $table->date('data');
                $table->decimal('valor', 12, 2);
                $table->unsignedInteger('fornecedores_id')->nullable();
                $table->string('status', 20)->default('pendente');
                $table->timestamps();
                $table->foreign('fornecedores_id')->references('id')->on('fornecedores')->nullOnDelete();
                $table->index(['status', 'data']);
            });
        }
        if (! Schema::hasTable('pagamentos')) {
            Schema::create('pagamentos', function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('compra_id');
                $table->decimal('valor', 12, 2);
                $table->date('data');
                $table->timestamps();
                $table->foreign('compra_id')->references('id')->on('compras')->restrictOnDelete();
                $table->index(['compra_id', 'data']);
            });
        }
        foreach (['fornecedores', 'compras'] as $name) {
            if (! Schema::hasColumn($name, 'deleted_at')) {
                Schema::table($name, fn (Blueprint $table) => $table->softDeletes());
            }
        }
        Schema::table('compras', function (Blueprint $table): void {
            $table->date('vencimento')->nullable()->index();
            $table->string('descricao', 500)->nullable();
        });
        Schema::table('pagamentos', function (Blueprint $table): void {
            $table->timestamp('estornado_em')->nullable();
            $table->string('motivo_estorno', 500)->nullable();
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role', 20)->default('consulta');
            $table->boolean('ativo')->default(true);
        });
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('acao', 40);
            $table->string('entidade', 80);
            $table->unsignedBigInteger('entidade_id')->nullable();
            $table->json('antes')->nullable();
            $table->json('depois')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
        Schema::create('notification_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('compra_id');
            $table->date('dia');
            $table->unique(['user_id', 'compra_id', 'dia']);
        });
    }

    public function down(): void
    {
        throw new RuntimeException('Migração de adoção do legado: restaure um backup para reverter sem perder dados.');
    }
};
