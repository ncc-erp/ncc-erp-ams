<?php

namespace App\Http\Transformers;

class DatatablesTransformer
{
    /**
     * @OA\Schema(
     *     schema="DatatablesResponse",
     *     type="object",
     *     description="Standard Datatables response format",
     *     @OA\Property(property="total", type="integer", example=100, description="Total number of items"),
     *     @OA\Property(
     *         property="rows",
     *         type="array",
     *         description="Array of data items",
     *         @OA\Items(type="object", description="Individual data item")
     *     )
     * )
     */
    public function transformDatatables($objects, $total = null)
    {
        (isset($total)) ? $objects_array['total'] = $total : $objects_array['total'] = count($objects);
        $objects_array['rows'] = $objects;

        return $objects_array;
    }
}
