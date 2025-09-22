<?php

namespace App\Http\Requests;

/**
 * @OA\Schema(
 *     schema="AssetCheckinRequest",
 *     title="Asset Checkin Request",
 *     description="Dữ liệu yêu cầu để thực hiện check-in tài sản",
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         description="Tên mới cho tài sản (tùy chọn)",
 *         example="MacBook Pro 2021"
 *     ),
 *     @OA\Property(
 *         property="status_id",
 *         type="integer",
 *         description="ID trạng thái mới cho tài sản",
 *         example=2
 *     ),
 *     @OA\Property(
 *         property="location_id",
 *         type="integer",
 *         description="ID địa điểm mới cho tài sản",
 *         example=5
 *     ),
 *     @OA\Property(
 *         property="checkin_at",
 *         type="string",
 *         format="date",
 *         description="Ngày check-in (Y-m-d)",
 *         example="2023-10-25"
 *     ),
 *     @OA\Property(
 *         property="note",
 *         type="string",
 *         description="Ghi chú khi check-in tài sản",
 *         example="Thiết bị đã được trả lại trong tình trạng tốt"
 *     )
 * )
 */
class AssetCheckinRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [];
    }

    public function response(array $errors)
    {
        return $this->redirector->back()->withInput()->withErrors($errors, $this->errorBag);
    }
}
