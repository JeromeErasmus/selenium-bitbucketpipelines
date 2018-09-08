<?php

namespace App\Models;
use Elf\Application\Application;
use Illuminate\Database\Eloquent\SoftDeletes;


class Transaction extends EloquentModel
{

    protected $primaryKey = "transaction_id";
    protected $table = "transactions";
    public $timestamps = false;

    protected $fieldMap = [
        'id' => [
            'name'  => 'transaction_id',
            'rules' => ''
        ],
        'response.summaryCode' => [
            'name'  => 'summary_code',
            'rules' => 'required'
        ],
        'response.responseCode' => [
            'name'  => 'response_code',
            'rules' => 'required'
        ],
        'response.text' => [
            'name'  => 'response_description',
            'rules' =>'required'
        ],
        'response.RRN' => [
            'name'  => 'rrn',
            'rules' => ''
        ],
        'response.settlementDate' => [
            'name'  => 'settlement_date',
            'rules' => '',
        ],
        'response.unknownRecheckCount' => [
            'name'  => 'unknown_recheck_count',
            'rules' => ''
        ],
        'response.referenceNo' => [
            'name'  => 'reference_no',
            'rules' => ''
        ],
        'response.orderNumber' => [
            'name'  => 'order_no',
            'rules' => ''
        ],
        'response.cardSchemeName' => [
            'name'  => 'card_scheme_name',
            'rules' => ''
        ],
        'response.creditGroup' => [
            'name' => 'credit_group'
        ],
        'response.transactionDate' => [
            'name'  => 'transaction_date',
            'rules' =>''
        ],
        'response.authId' => [
            'name' =>  'auth_id',
            'rules' => '',
        ],
        'response.cvnResponse' => [
            'name' =>  'cvn_response',
            'rules' => '',
        ],
        'response.agAccountType' => [
            'name' =>  'ag_account_type',
            'rules' => '',
        ]


    ];

    public function invoice()
    {
        return $this->belongsTo('App\Models\Invoice', 'transaction_id', 'inv_tra_id');
    }
    
    /**
     * Get all transactions with: 
     *      state unknown 
     *      that have not been checked two times by RetryTransactionCommand
     *      are not for account clients 
     * @param type $query
     */
    public function scopeReCheck($query, $params)
    {
        $query->where('summary_code', $params['summaryCode'])
                ->where('unknown_recheck_count', '<', $params['unknownRecheckCount'])
                ->whereNotIn('response_code', ['ACC', 'ACCR'])
                ->with('invoice');
    }

}