<?php

namespace App\Http\Controllers\API\Cashier;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PrinterController extends Controller
{
    public function print(Request $request)
    {
        $printerIP = '192.168.100.17'; // Replace with your printer's IP
        $printerPort = 9100; // Default port for RAW printing

        $data = $request->input('data'); // Get the raw print data from the request

        try {
            // Create a socket connection to the printer
            $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            if ($socket === false) {
                return response()->json(['error' => 'Could not create socket: ' . socket_strerror(socket_last_error())], 500);
            }

            $result = socket_connect($socket, $printerIP, $printerPort);
            if ($result === false) {
                return response()->json(['error' => 'Could not connect to printer: ' . socket_strerror(socket_last_error($socket))], 500);
            }

            // Send data to the printer
            $writeResult = socket_write($socket, $data, strlen($data));
            if ($writeResult === false) {
                return response()->json(['error' => 'Could not write to printer: ' . socket_strerror(socket_last_error($socket))], 500);
            }

            $closeResult = socket_close($socket);
            if ($closeResult === false) {
                return response()->json(['error' => 'Could not close socket: ' . socket_strerror(socket_last_error($socket))], 500);
            }

            return response()->json(['message' => 'Print job sent successfully!'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
