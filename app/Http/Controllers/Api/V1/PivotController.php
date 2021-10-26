<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\ClientException;

class PivotController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // conecta al endpoint de las apis internas
        $endpoint = "https://apitest-bt.herokuapp.com/api/v1/imagenes";
        $client = new Client();
        $response = $client->get($endpoint, [
            'headers' => [
                'Accept' => 'application/json',

                "user" => "User123",

                "password" => "Password123",
            ],
        ]);



        $rawBody = $response->getBody();
        $data = json_decode($rawBody, 1);

        $filteredImages = [];

        foreach($data as $temp){
            $base64 = isset($temp['base64']) ? $temp['base64'] : null;
            if ($this->isValidBase64($base64)){
                $filteredImages[] = $temp;
            }
        }

        return response()->json($filteredImages);
    }



    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // verifica si vienen los parametros escenciales
        if ($request->archivo and $request->nombre){
            // se verifica la extension
            $extension = strtolower($request->archivo->extension());
            $nombre = $request->nombre;

            if (in_array($extension, ['png', 'bmp', 'jpg', 'jpeg', 'svg', 'gif'])){

                // se obtiene el archivo en binario y se convierte a base64
                $file = $request->archivo->get();
                $base64 = base64_encode($file);

                // se prepara la peticion a las apis internas
                $endpoint = "https://apitest-bt.herokuapp.com/api/v1/imagenes";
                $client = new Client();
                $response = $client->post($endpoint, [
                    'headers' => [
                        'Accept' => 'application/json',

                        "user" => "User123",

                        "password" => "Password123",
                    ],
                    'form_params' => [
                        'imagene' => [
                            "nombre" => $nombre,
                            "base64" => $base64
                        ]
                    ]
                ]);

            }else{
                return response()->json([
                    'resultado' => false,
                    'descripcion' => "El archivo no posee una extension de tipo imagen"
                ]);
            }

            return response()->json([
                'resultado' => true,
                'descripcion' => "Imagen almacenada correctamente"
            ]);

        }else{
            if ($request->archivo){
                return response()->json([
                    'resultado' => false,
                    'descripcion' => "El campo 'nombre' es obligatorio"
                ]);
            }else{
                return response()->json([
                    'resultado' => false,
                    'descripcion' => "El campo archivo es obligatorio y no se ha subido ninguno"
                ]);
            }
        }

    }



    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // conecta al endpoint de las apis internas
        $endpoint = "https://apitest-bt.herokuapp.com/api/v1/imagenes/" . $id;
        $client = new Client();

        try{
            $response = $client->get($endpoint, [
                'headers' => [
                    'Accept' => 'application/json',

                    "user" => "User123",

                    "password" => "Password123",
                ],
            ]);
        }catch(ClientException $e){
            return response()->json([
                'resultado' => false,
                'descripcion' => "No se encuentra ningun registro en la base de datos con el id: " . $id
            ]);
        }

        $rawBody = $response->getBody();
        $data = json_decode($rawBody, 1);

        return response()->json($data);
    }



    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        // verifica si vienen los parametros escenciales
        if ($request->archivo and $request->nombre){
            // se verifica la extension
            $extension = strtolower($request->archivo->extension());
            $nombre = $request->nombre;

            if (in_array($extension, ['png', 'bmp', 'jpg', 'jpeg', 'svg', 'gif'])){

                // se obtiene el archivo en binario y se convierte a base64
                $file = $request->archivo->get();
                $base64 = base64_encode($file);

                // se prepara la peticion a las apis internas
                $endpoint = "https://apitest-bt.herokuapp.com/api/v1/imagenes/" . $id;
                $client = new Client();

                try{
                    $response = $client->put($endpoint, [
                        'headers' => [
                            'Accept' => 'application/json',

                            "user" => "User123",

                            "password" => "Password123",
                        ],
                        'form_params' => [
                            'imagene' => [
                                "nombre" => $nombre,
                                "base64" => $base64
                            ]
                        ]
                    ]);
                }catch(ClientException $e){
                    return response()->json([
                        'resultado' => false,
                        'descripcion' => "No se encuentra ningun registro en la base de datos con el id: " . $id . " por lo que no se puede actualizar la data"
                    ]);
                }

            }else{
                return response()->json([
                    'resultado' => false,
                    'descripcion' => "El archivo no posee una extension de tipo imagen"
                ]);
            }

            return response()->json([
                'resultado' => true,
                'descripcion' => "Imagen almacenada correctamente"
            ]);

        }else{
            if ($request->archivo){
                return response()->json([
                    'resultado' => false,
                    'descripcion' => "El campo 'nombre' es obligatorio"
                ]);
            }else{
                return response()->json([
                    'resultado' => false,
                    'descripcion' => "El campo archivo es obligatorio y no se ha subido ninguno"
                ]);
            }
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function isValidBase64($element){
        if ($element != null){
            // si contiene caracteres que no pertenecen a base64
            $badChars = preg_match_all('/[^a-zA-Z0-9+\/=]/m', $element, $matches, PREG_OFFSET_CAPTURE);
            if ($badChars){
                return false;
            }

            // si no es multiplo de 4 no pertenece a base64
            if (strlen($element) % 4){
                return false;
            }


            try{
                // si la funcion de php no logra decodificarlo en modo estricto
                $decoded = base64_decode($element, true);
                if (!$decoded){
                    return false;
                }
            }
            catch(Exception $e){
                // si hubo un error inesperado por no poder ser decodificado
                return false;
            }

        }else{
            // si es null
            return false;
        }

        // si no rompio ninguna regla esta correcta su codificacion base64
        return true;
    }
}
