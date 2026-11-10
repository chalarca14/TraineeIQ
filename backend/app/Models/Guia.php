<?php

namespace App\Models;

use App\Services\ExperienciaService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Guia extends Model
{
    use HasFactory;

    protected $table = 'guias';

    // XP por semana de una guia personal. Se calcula una vez por peticion
    private ?Collection $progresoExperiencia = null;

    protected $fillable = [
        'nombre',
        'archivo_pdf',
        'hash_archivo',
        'semanas_totales',
        'grupo_id',
        'usuario_id',
        'origen', // 'grupo' o 'personal'
    ];

    /**
     * Indica si una semana ya esta liberada para este usuario. El instructor del grupo
     * ve todas. El estudiante de grupo llega hasta la semana_actual que fija el
     * instructor: conserva las pasadas y no ve las futuras. En Modo Personal cada
     * semana se abre con la experiencia ganada en la anterior.
     */
    public function semanaLiberadaPara(User $user, int $numeroSemana): bool
    {
        if ($this->origen === 'personal') {
            $ultima = app(ExperienciaService::class)->ultimaSemanaLiberada($this->progresoExperiencia());

            return $numeroSemana <= $ultima;
        }

        if ($this->grupo?->instructor_id === $user->id) {
            return true;
        }

        return $numeroSemana <= (int) $this->grupo?->semana_actual;
    }

    /**
     * XP de cada semana de esta guia personal. Se guarda en la instancia para no
     * consultar la base cada vez que se revisa una semana o un tema.
     */
    public function progresoExperiencia(): Collection
    {
        return $this->progresoExperiencia ??= app(ExperienciaService::class)->progresoSemanas($this);
    }

    /**
     * Quita de la guia los temas de semanas que este usuario aun no tiene liberadas.
     * Espera cargadas las relaciones grupo y temas.semana, para no consultar tema por tema.
     * Un tema sin semana no tiene nada que bloquear y se deja visible.
     */
    public function ocultarTemasBloqueadosPara(User $user): static
    {
        $visibles = $this->temas
            ->filter(fn (GuiaTema $tema) => $tema->semana === null
                || $this->semanaLiberadaPara($user, $tema->semana->numero_semana))
            ->values();

        // La semana solo se cargo para filtrar: no viaja en la respuesta
        $visibles->each->makeHidden('semana');

        return $this->setRelation('temas', $visibles);
    }

     // Una guía puede pertenecer a un grupo (si es de origen grupal).

    public function grupo()
    {
        return $this->belongsTo(Grupo::class);
    }

    //Una guía puede pertenecer a un usuario (si es de origen personal).

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    //Una guía se divide en semanas.

    public function semanas()
    {
        return $this->hasMany(GuiaSemana::class, 'guia_id')->orderBy('numero_semana');
    }

    //Una guía se divide en varios temas o módulos.
    
    public function temas()
    {
        return $this->hasMany(GuiaTema::class, 'guia_id');
    }

    //Una guía tiene muchas recomendaciones generadas por la IA.
    
    public function recomendaciones()
    {
        return $this->hasMany(Recomendacion::class, 'guia_id');
    }

    //Una guía tiene varios mini proyectos sugeridos.

    public function miniProyectos()
    {
        return $this->hasMany(MiniProyecto::class, 'guia_id');
    }
}