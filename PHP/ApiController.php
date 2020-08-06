<?php

namespace App\Http\Controllers;

use \Carbon\Carbon;
use File;
use App\Models\Movie;
use App\Models\Series;
use App\Models\MovieGenre;
use App\Models\SeriesGenre;
use Illuminate\Http\Request;
use Image;

class ApiController extends Controller
{
    const API_PATH = 'https://api.themoviedb.org/3';
    const API_IMAGE_PATH = 'https://image.tmdb.org/t/p/w500';
    const FULL_LIST_IDS_PATH = 'http://files.tmdb.org/p/exports';
    /* http://files.tmdb.org/p/exports/movie_ids_04_18_2020.json.gz */

    public function extractGZip($fileName = null)
    {
        return gzfile($fileName);
    }

    public function importSeries(Request $request)
    {
        dd('no');

        $rows = $this->extractGZip(self::FULL_LIST_IDS_PATH . '/tv_series_ids_' . Carbon::yesterday()->format('m_d_Y') . '.json.gz');

        if ($rows) {
            foreach ($rows as $rowJSON) {
                $row = json_decode($rowJSON);

                if ($row->popularity > 1.0) {
                    $this->importShow($row->id);
                }
            }
        } 
    }

    public function updateSeries(Request $request)
    {
        $query = http_build_query([
            'api_key' => config('app.tmdb_api_key'),
            'start_date' => Carbon::yesterday()->format('d-m-Y'),
            'end_date' => Carbon::today()->format('d-m-Y'),
            'page' => 1
        ]);

        $rawRows = @file_get_contents(self::API_PATH . '/tv/changes?' . $query);
        
        if ($rawRows) {
            $rows = json_decode($rawRows, true);

            foreach ($rows['results'] as $row) {
                if (!isset($row['adult']) || !$row['adult']) {
                    $this->importShow($row['id']);
                }
            }
        }
    }

    public function importShow($seriesId = 1)
    {
        $rawData = @file_get_contents(self::API_PATH . '/tv/' . $seriesId . '?api_key=' . env('TMDB_API_KEY'));
        
        if ($rawData) {
            $tmdbData = json_decode($rawData);

            Series::updateOrInsert(
                [
                    'tmdb_id' => $tmdbData->id
                ],
                [
                    'name' => $tmdbData->name,
                    'slug' => str_slug($tmdbData->name),
                    'poster' => $tmdbData->poster_path ?? null,
                    'cover' => $tmdbData->backdrop_path ?? null,
                    'tmdb_score' => $tmdbData->vote_average,
                    'tmdb_score_count' => $tmdbData->vote_count,
                    'release_date' => $tmdbData->first_air_date,
                    'end_date' => $tmdbData->status == 'Ended' ? $tmdbData->last_air_date : null,
                    'runtime' => optional($tmdbData->episode_run_time)[0],
                    'body' => '<p>' . $tmdbData->overview . '</p>',
                    'seo_description' => $tmdbData->overview,
                    'public' => 1,
                    'updated_at' => \Carbon\Carbon::now(),
                ]
            );
            $series = Series::where('tmdb_id', $tmdbData->id)->first();

            // Sync genres
            if (isset($tmdbData->genres) && is_array($tmdbData->genres)) {
                $genres = [];
                foreach ($tmdbData->genres as $tmdbGenre) {          
                    SeriesGenre::updateOrInsert(
                        [
                            'label' => $tmdbGenre->id
                        ],
                        [
                            'name' => $tmdbGenre->name,
                            'slug' => str_slug(trans('genres.' . $tmdbGenre->name)),
                        ]
                    );

                    $genre = SeriesGenre::where('label', $tmdbGenre->id)->first();
                    $genres[] = $genre->id;
                }

                $series->genres()->sync($genres);
            }

            // seasons and episodes
            if (isset($tmdbData->seasons) && is_array($tmdbData->seasons)) {
                foreach ($tmdbData->seasons as $tmdbSeason) {
                    if ($tmdbSeason->season_number > 0) {
                        $series->seasons()->updateOrCreate([
                            'season' => $tmdbSeason->season_number,
                        ],
                        [
                            'episode' => $tmdbSeason->episode_count,
                        ]);
                    }
                }
            }

            // Sync alias
            if ($tmdbData->original_name != $tmdbData->name) {
                if (!$series->aliases()->where('name', $tmdbData->original_name)->first()) {
                    $series->aliases()->create(['name' => $tmdbData->original_name]);
                }
            }

            if ($tmdbData->poster_path && !File::exists(uploadsAnyway('series', false, $tmdbData->poster_path))) {
                $poster = @file_get_contents(self::API_IMAGE_PATH . $tmdbData->poster_path);

                if ($poster && isValidBinary($poster)) {
                    Image::make(self::API_IMAGE_PATH . $tmdbData->poster_path)
                        ->save(uploadsAnyway('series', false, $tmdbData->poster_path));
                }
            }

            // if ($tmdbData->backdrop_path && !File::exists(uploadsAnyway('series', false, $tmdbData->backdrop_path))) {
            //     $backdrop = @file_get_contents(self::API_IMAGE_PATH . $tmdbData->backdrop_path);

            //     if ($backdrop && isValidBinary($backdrop)) {
            //         Image::make(self::API_IMAGE_PATH . $tmdbData->backdrop_path)
            //             ->save(uploadsAnyway('series', false, $tmdbData->backdrop_path));
            //     }
            // }
        }
    }


    public function importMovies(Request $request)
    {
        dd('no');
        
        $rows = $this->extractGZip(self::FULL_LIST_IDS_PATH . '/movie_ids_' . Carbon::yesterday()->format('m_d_Y') . '.json.gz');

        if ($rows) {
            foreach ($rows as $rowJSON) {
                $row = json_decode($rowJSON);

                if ($row->popularity > 2.0 && !$row->adult) {
                    $this->importMovie($row->id);
                }
            }
        }
    }

    public function updateMovies(Request $request)
    {
        $query = http_build_query([
            'api_key' => config('app.tmdb_api_key'),
            'start_date' => Carbon::yesterday()->format('d-m-Y'),
            'end_date' => Carbon::today()->format('d-m-Y'),
            'page' => 1
        ]);

        $rawRows = @file_get_contents(self::API_PATH . '/movie/changes?' . $query);
        
        if ($rawRows) {
            $rows = json_decode($rawRows, true);

            foreach ($rows['results'] as $row) {
                if (!$row['adult']) {
                    $this->importMovie($row['id']);
                }
            }
        }
    }

    public function importMovie($movieId)
    {
        $rawData = @file_get_contents(self::API_PATH . '/movie/' . $movieId . '?api_key=' . env('TMDB_API_KEY'));
        if ($rawData) {
            $tmdbData = json_decode($rawData);

            Movie::updateOrInsert(
                [
                    'tmdb_id' => $tmdbData->id,
                ],
                [
                    'imdb_id' => $tmdbData->imdb_id,
                    'name' => $tmdbData->title,
                    'slug' => str_slug($tmdbData->title),
                    'poster' => $tmdbData->poster_path,
                    'cover' => $tmdbData->backdrop_path,
                    'tmdb_score' => $tmdbData->vote_average,
                    'tmdb_score_count' => $tmdbData->vote_count,
                    'release_date' => $tmdbData->release_date,
                    'runtime' => $tmdbData->runtime,
                    'trailer_url' => $tmdbData->video ?? null,
                    'body' => '<p>' . $tmdbData->overview . '</p>',
                    'seo_description' => $tmdbData->overview,
                    'public' => 1,
                    'updated_at' => \Carbon\Carbon::now(),
                ]
            );
            $movie = Movie::where('tmdb_id', $tmdbData->id)->first();

            // Sync genres
            if (isset($tmdbData->genres) && is_array($tmdbData->genres)) {
                $genres = [];
                foreach ($tmdbData->genres as $tmdbGenre) {          
                    MovieGenre::updateOrInsert(
                        [
                            'label' => $tmdbGenre->id
                        ],
                        [
                            'name' => $tmdbGenre->name,
                            'slug' => str_slug(trans('genres.' . $tmdbGenre->name)),
                        ]
                    );

                    $genre = MovieGenre::where('label', $tmdbGenre->id)->first();
                    $genres[] = $genre->id;
                }

                $movie->genres()->sync($genres);
            }

            // Sync alias
            if ($tmdbData->original_title != $tmdbData->title) {
                if (!$movie->aliases()->where('name', $tmdbData->original_title)->first()) {
                    $movie->aliases()->create(['name' => $tmdbData->original_title]);
                }
            }

            if ($tmdbData->poster_path && !File::exists(uploadsAnyway('movie', false, $tmdbData->poster_path))) {
                $poster = @file_get_contents(self::API_IMAGE_PATH . $tmdbData->poster_path);

                if ($poster && isValidBinary($poster)) {
                    Image::make(self::API_IMAGE_PATH . $tmdbData->poster_path)
                        ->save(uploadsAnyway('movie', false, $tmdbData->poster_path));
                }
            }

            // if ($tmdbData->backdrop_path && !File::exists(uploadsAnyway('movie', false, $tmdbData->backdrop_path))) {
            //     $backdrop = @file_get_contents(self::API_IMAGE_PATH . $tmdbData->backdrop_path);

            //     if ($backdrop && isValidBinary($backdrop)) {
            //         Image::make(self::API_IMAGE_PATH . $tmdbData->backdrop_path)
            //             ->save(uploadsAnyway('movie', false, $tmdbData->backdrop_path));
            //     }
            // }
        }
    }
}
