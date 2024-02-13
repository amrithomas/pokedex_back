<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;

class PokemonController extends Controller
{
    public function getAllPokemon(Request $request)
    {
        $limit = $request->input('limit', 20);
        $offset = $request->input('offset', 0);

        $url = "https://pokeapi.co/api/v2/pokemon/?offset=$offset&limit=$limit";

        $client = new Client();
        $response = $client->request('GET', $url);
        $pokemonData = json_decode($response->getBody(), true);

        $pokemonList = [];

        foreach ($pokemonData['results'] as $pokemon) {
            $pokemonResponse = $client->request("GET", $pokemon['url']);
            $pokemonDetails = json_decode($pokemonResponse->getBody(), true);

            $pokemonList[] = [
                'name' => $pokemonDetails['name'],
                'id' => $pokemonDetails['id'],
                'image' => $pokemonDetails['sprites']['front_default']
            ];
        }

        // Retorna a lista de Pokémon
        return response()->json($pokemonList);
    }


    public function getPokemon($name)
    {
        $client = new Client();
        $response = $client->request('GET', 'https://pokeapi.co/api/v2/pokemon/' . $name);
        $pokemonData = json_decode($response->getBody(), true);

        // Faça algo com os dados do Pokémon, como retorná-los como JSON
        return response()->json($pokemonData);
    }

    public function getPokemonByGeneration()
    {
        // Inicializando uma array para armazenar todos os Pokémon da primeira geração
        $generationPokemon = [];

        // URL da primeira geração
        $firstGenerationUrl = "https://pokeapi.co/api/v2/generation/1/";

        // Obtendo os detalhes da primeira geração
        $generationResponse = Http::get($firstGenerationUrl);
        $generationDetails = $generationResponse->json();

        // Verificar se a solicitação da primeira geração foi bem-sucedida
        if (!$generationResponse->successful()) {
            return response()->json(['error' => 'Failed to fetch Pokémon list'], 500);
        }

        // Continuar enquanto a próxima página estiver disponível
        $nextPage = "https://pokeapi.co/api/v2/pokemon/";
        while ($nextPage) {
            // Fazer solicitação para a próxima página de resultados
            $response = Http::get($nextPage);

            // Verificar se a solicitação foi bem-sucedida
            if (!$response->successful()) {
                return response()->json(['error' => 'Failed to fetch Pokémon list'], 500);
            }

            // Adicionar os Pokémon da página atual à lista
            $pokemonList = $response->json()['results'];
            foreach ($pokemonList as $pokemon) {
                $pokemonResponse = Http::get($pokemon['url']);
                $pokemonDetails = $pokemonResponse->json();

                // Verificar se o Pokémon é da primeira geração
                return response()->json($pokemonDetails);
                if ($pokemonDetails['generation']['url'] == $firstGenerationUrl) {
                    $generationPokemon[] = [
                        'name' => $pokemonDetails['name'],
                        'id' => $pokemonDetails['id'],
                        'image' => $pokemonDetails['sprites']['front_default']
                    ];
                }
            }

            // Atualizar a próxima página, se houver
            $nextPage = $response->json()['next'];
        }

        // Retornando os Pokémon da primeira geração encontrados
        return response()->json($generationPokemon);
    }
}
