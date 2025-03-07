<?php

namespace App\Http\Controllers\API\Cashier;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\PrintConnectors\FilePrintConnector;
use Mike42\Escpos\CapabilityProfile;

class PrinterController extends Controller
{
    public function print(Request $request)
    {
        $printerIP = '192.168.100.17'; // Replace with your BIXOLON printer's IP
        $printerPort = 9100; // Default port for RAW printing
        $printerName = 'BIXOLON SRP-E300'; // Replace with your printer's name
        $ytes = "mb://Guest@DESKTOP-O1Q4G1Q\BIXOLON_SRP_E300";
        try {
            $connector = new WindowsPrintConnector("\\\\DESKTOP-O1Q4G1Q\\BIXOLON_SRP_E300");
            // $connector = new NetworkPrintConnector($printerIP, $printerPort);
            // $connector = new WindowsPrintConnector("LPT1");
            // Create a Printer object
            $printer = new Printer($connector);

            // Print text
            $printer->text("Hello, this is a test print from Laravel!\n");
            $printer->text("Thank you for using WindowsPrintConnector.\n");

            // Cut the paper (if supported by the printer)
            $printer->cut();

            // Close the printer connection
            $printer->close();

            return response()->json(['message' => 'Print job sent successfully!'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        // try {
        //     // Create a network connector
        //     // $connector = new WindowsPrintConnector($printerName);

        //     // $connector = new FilePrintConnector("php://stdout");

        //     $connector = new NetworkPrintConnector($printerIP, $printerPort);

        //     // $profile = CapabilityProfile::load("simple");
        //     // $connector = new WindowsPrintConnector("smb://DESKTOP-O1Q4G1Q/BIXOLON_SRP_E300");

        //     // $connector = new WindowsPrintConnector("\\\\DESKTOP-O1Q4G1Q\\BIXOLON_SRP_E300");

        //     // $printer = new Printer($connector, $profile);

        //     // Initialize the printer
        //     $printer = new Printer($connector);

        //     // Print data (from the request or default message)
        //     // $data = $request->input('data', "Hello, BIXOLON SRP-E300!\n");
        //     // $printer->text($data);
        //     $printer->setJustification(Printer::JUSTIFY_CENTER);
        //     $printer->text("=== RECEIPT ===\n");
        //     $printer->setJustification(Printer::JUSTIFY_LEFT);
        //     $printer->text("Item 1: $10.00\n");
        //     $printer->text("Item 2: $5.00\n");
        //     $printer->setJustification(Printer::JUSTIFY_RIGHT);
        //     $printer->text("Total: $15.00\n");
        //     $printer->cut();

        //     // Close the printer connection
        //     $printer->close();

        //     return response()->json(['message' => 'Print job sent successfully!'], 200);
        // } catch (\Exception $e) {
        //     return response()->json(['error' => $e->getMessage()], 500);
        // }
    }

    public function print4(Request $request)
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

    public function print5(Request $request)
    {
        $printerIP = '192.168.100.17'; // Printer IP
        $printerPort = 9100; // Printer Port
        $data = "Hello, this is a test print!\n"; // Data to print

        try {
            $socket = fsockopen($printerIP, $printerPort, $errno, $errstr, 30);
            if (!$socket) {
                throw new \Exception("Unable to connect: $errstr ($errno)");
            }

            fwrite($socket, $data); // Send data to the printer
            fclose($socket); // Close the connection
            echo "Print job sent successfully!";
        } catch (\Exception $e) {
            echo "Failed to print: " . $e->getMessage();
        }
    }
}
