<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Wallet;
use App\Models\Transaction;

class WalletController extends Controller
{
    public function deposit(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'amount' => 'required|numeric'
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => 'error',
                'error' => $validator->errors()->first()
            ], 406);
        }

        try {
            DB::beginTransaction();

            $checkWallet = Wallet::where('user_id', auth()->user()->id)->first();

            if(!$checkWallet) {
                $wallet = new Wallet;
                $wallet->user_id = auth()->user()->id;
                $wallet->balance = $request->amount;
                $wallet->save();
            } else {
                $checkWallet->balance = $checkWallet->balance + $request->amount;
                $checkWallet->save();
            }

            $transaction = new Transaction;
            $transaction->user_id = auth()->user()->id;
            $transaction->transaction_type = "credit";
            $transaction->amount = $request->amount;
            $transaction->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'deposit success'
            ], 200);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'error' => "Request failed"
            ], 400);
        }
    }

    public function withdraw(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'amount' => 'required|numeric'
        ]);

        if($validator->fails()){
            return response()->json([
                'status' => 'error',
                'error' => $validator->errors()->first()
            ], 406);
        }

        try {
            DB::beginTransaction();

            $checkWallet = Wallet::where('user_id', auth()->user()->id)->first();

            if(!$checkWallet) {
                return response()->json([
                    'status' => 'error',
                    'error' => "Insufficient funds"
                ], 400);
            } elseif($request->amount > $checkWallet->balance) {
                return response()->json([
                    'status' => 'error',
                    'error' => "Insufficient funds"
                ], 400);
            } else {
                $checkWallet->balance = $checkWallet->balance - $request->amount;
                $checkWallet->save();
            }

            $transaction = new Transaction;
            $transaction->user_id = auth()->user()->id;
            $transaction->transaction_type = "debit";
            $transaction->amount = $request->amount;
            $transaction->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'withdrawal success'
            ], 200);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'error' => "Request failed"
            ], 400);
        }
    }

    public function userTransactions() {
        $transactions = Transaction::with('user')->where(['user_id' => auth()->user()->id])->get();

        return response()->json($transactions, 200);
    }
}
