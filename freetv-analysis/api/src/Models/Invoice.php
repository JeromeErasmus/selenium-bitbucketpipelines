<?php

namespace App\Models;

use App\Models\EloquentModel;


class Invoice extends EloquentModel
{ 

    protected $primaryKey = "inv_id";
    protected $table = 'invoices';
    public $timestamps = true;
    
    protected $dates = ['updated_at', 'created_at'];

    protected $fieldMap = [
        'id' => [
            'name' => "inv_id",
        ],
        'documentId' => [
            'name' => 'inv_jum_id',
            'rules' => 'required',
        ],
        'transactionId' => [
            'name' => 'inv_tra_id',
        ],
        'invoiceBlob' => [
            'name' => 'inv_blob',
        ],
        'createdAt' => [
            'name' => 'created_at',
        ],
        'amountExGst' => [
            'name' => 'inv_amount_ex_gst',
        ],
        'gst' => [
            'name' => 'inv_gst',
        ],
        'amountIncGst' => [
            'name' => 'inv_amount_inc_gst',
        ],
        'jobId' => [
            'name' => 'inv_job_id',
        ],
        'updatedAt' => [
            'name' => 'updated_at',
        ],
        'invoiceType' => [
            'name' => 'inv_invoice_type_id'
        ],
        'invoiceComment' => [
            'name' => 'inv_comment'
        ],
    ];
    

    public function transaction()
    {
        return $this->hasOne('App\Models\Transaction', 'inv_tra_id', 'transaction_id');
    }
}
