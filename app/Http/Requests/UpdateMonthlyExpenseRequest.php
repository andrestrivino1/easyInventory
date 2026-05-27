<?php

namespace App\Http\Requests;

/**
 * Misma validación que Store; la regla de unicidad ya ignora el propio
 * registro al detectar el parámetro de ruta {gasto}.
 */
class UpdateMonthlyExpenseRequest extends StoreMonthlyExpenseRequest
{
}
