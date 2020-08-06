<?php

namespace App\Http\Controllers;

use DB;
use Auth;
use App\Models\Series;
use App\Models\SeriesList;
use App\Models\SeriesGenre;
use App\Models\SeriesRating;
use App\Models\SeriesUpload;
use Illuminate\Http\Request;

class SeriesController extends Controller
{
    public function index(Request $request)
    {
        $series = Series::
            when($request->name, function ($query, $name) {
                $query->where(function ($query) use ($name) {
                    $query->where('name', 'LIKE', '%' . $name . '%')
                        ->orWhereHas('aliases', function ($query) use ($name) {
                            $query->where('name', 'LIKE', '%' . $name . '%');
                        });
                });
            })
            ->when($request->year_from, function ($query, $year) {
                $query->whereYear('release_date', '>=', $year);
            })
            ->when($request->year_to, function ($query, $year) {
                $query->whereYear('release_date', '<=', $year);
            })
            ->when($request->uploads, function ($query, $uploads) {
                $query->whereHas('uploads', function ($query) use ($uploads) {
                    $query->whereIn('lang_id', $uploads);
                });
            })
            ->when($request->genres, function ($query, $genres) {
                $query->whereHas('genres', function ($query) use ($genres) {
                    $query->whereIn('genre_id', $genres)
                        ->having(DB::raw('count(*)'), '=', count($genres));
                });
            })
            ->when($request->exclusion_genres, function ($query, $genres) {
                $query->whereHas('genres', function ($query) use ($genres) {
                    $query->whereIn('genre_id', $genres)
                        ->having(DB::raw('count(*)'), '=', 0);
                });
            })
            ->when($request->score_above > 1 ? $request->score_above : false, function ($query, $score) {
                $query->whereHas('ratings', function ($query) use ($score) {
                    $query->having(DB::raw('AVG(value)'), '>=', $score);
                });
            })
            ->paginate(32);

        return view('series.index')->with('shows', $series);
    }

    public function single($id)
    {
    	$series = Series::find($id);

    	if ($series) {
            if (Auth::check()) {
                Auth::user()->addToHistory('series', $series->id);
            }

            $series->increment('views');
        	return view('series.single')
                ->with('series', $series);
        }

        return abort(404);
    }

    public function watch($seriesId, $slug, $season, $episode)
    {
        $series = Series::find($seriesId);

        if ($series) {
            $seriesUploads = $series->publicUploads()
                ->where([
                    'season' => $season,
                    'episode' => $episode,
                ])
                ->orderByDesc('views')
                ->get();

            return view('series.watch')->with('series', $series)->with('seriesUploads', $seriesUploads);
        }

        return abort(404);
    }

    public function openUpload(Request $request)
    {
        $upload = SeriesUpload::find($request->upload_id);

        if ($upload) {
            $upload->increment('views');
        }
    }

    public function rate(Request $request)
    {
        $rating = Auth::user()->seriesRatings->where('series_id', $request->series_id)->first();

        if ($rating) {
            $rating = SeriesRating::find($rating->id);
            $rating->value = $request->value;
            $rating->save();
        } else {
            $rating = Auth::user()->seriesRatings()->create([
                'series_id' => $request->series_id,
                'value' => $request->value,
            ]);
        }

        insertFeedItem('Series', $request->series_id, 'rated');

        forgetAvgRating('series', $request->series_id);
        forgetRatingsCount('series', $request->series_id);

        return [
            'type' => 'success',
            'message' => trans('movie.successful_rating'),
        ];
    }

    public function saveComment(Request $request)
    {
        $request->validate([
            'body' => 'required',
            'series_id' => 'required|int',
        ]);

        forgetCommentsCount('series', $request->series_id);

        $comment = Auth::user()->seriesComments()->create($request->all());

        return view('_partials.movie.comment_item', [
            'type' => 'series',
            'comment' => $comment,
        ]);
    }

    public function likeComment(Request $request)
    {
        $row = Auth::user()->seriesCommentLiked($request->comment_id);

        if ($row) {
            Auth::user()->seriesCommentVotes()->detach($request->comment_id);
            return -1;
        } else {
            Auth::user()->seriesCommentVotes()->attach($request->comment_id);
            return 1;
        }
    }

    public function addToList(Request $request)
    {
        $listRow = SeriesList::find($request->list_id);
        $pivotRow = Auth::user()->seriesLists()->wherePivot('series_id', $request->series_id)->first();

        if ($listRow->label == 'seen') {
            insertFeedItem('Series', $request->series_id, 'completed');
        }

        if (!in_array($listRow->label, ['add', 'remove'])) {
            if ($pivotRow) {
                $pivotRow->pivot->update(['list_id' => $request->list_id]);
            } else {
                Auth::user()->seriesLists()->attach([$request->series_id => ['list_id' => $request->list_id]]);
            }
        } else {
            Auth::user()->seriesLists()->detach($request->series_id);
        }
    }

    public function updateProgress(Request $request)
    {
        if (userSeriesProgress($request->series_id, $request->season, $request->episode)) {
            Auth::user()->
                seriesProgress()->
                where('series_id', $request->series_id)->
                wherePivot('season', $request->season)->
                wherePivot('episode', $request->episode)->
                detach();
        } else {
            Auth::user()->seriesProgress()->attach([
                $request->series_id => [
                    'season' => $request->season,
                    'episode' => $request->episode
                ]
            ]);

            insertFeedItem('Series', $request->series_id, 'episode_watched');
        }
    }

    public function updateProgressQuick(Request $request)
    {
        $series = Series::find($request->series_id);
        $season = $series->seasons()->where('season', $request->season)->first();

        if ($request->type == 'mark_season_as_watched') {
            for ($i = 1; $i <= $season->episode; $i++) {
                if (!userSeriesProgress($series->id, $season->season, $i)) {
                    Auth::user()->seriesProgress()->attach([
                        $request->series_id => [
                            'season' => $season->season,
                            'episode' => $i
                        ]
                    ]);
                }
            }

            insertFeedItem('Series', $series->id, 'episode_watched');
        } else if ($request->type == 'mark_season_as_unwatched') {
            Auth::user()->
                seriesProgress()->
                where('series_id', $series->id)->
                wherePivot('season', $season->season)->
                detach();
        } else if ($request->type == 'mark_series_as_watched') {
            foreach ($series->seasons as $currentSeason) {
                for ($i = 1; $i <= $currentSeason->episode; $i++) {
                    if (!userSeriesProgress($series->id, $currentSeason->season, $i)) {
                        Auth::user()->seriesProgress()->attach([
                            $request->series_id => [
                                'season' => $currentSeason->season,
                                'episode' => $i
                            ]
                        ]);
                    }
                }
            }

            insertFeedItem('Series', $series->id, 'completed');
        } else if ($request->type == 'mark_series_as_unwatched') {
            Auth::user()->seriesProgress()->
                where('series_id', $series->id)->
                detach();
        }
    }

    public function jsonList(Request $request)
    {
        return Series::publicSeries()->where('name', 'LIKE', '%' . $request->q . '%')->select('id', 'name AS text')->get();
    }

    public function genresJsonList(Request $request)
    {
        return SeriesGenre::query()->where('name', 'LIKE', '%' . $request->q . '%')->select('id', 'name AS text')->get();
    }

    public function jsonData(Request $request)
    {
        return Series::find($request->id)->seasons;
    }
}
