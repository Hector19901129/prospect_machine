<?php

namespace App\Services;

use App\MasterVideo;
use App\Services\FFMpeg\FFMpeg;
use App\Services\FFMpeg\FFProbe;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Urlbox\Screenshots\Facades\Urlbox;

class SummaryVideoService
{
    protected $ipService;

    protected $scoring;

    protected $audioFilterVar;
    
    public function __construct(
        FFMpeg $ffmpeg,
        FFProbe $ffprobe,
        ScoringService $scoring,
        IPService $ipService
    ) {
        $this->ffmpeg = $ffmpeg;
        $this->ffprobe = $ffprobe;
        $this->scoring = $scoring;
        $this->ipService = $ipService;
    }
    
    public function build(MasterVideo $video)
    {
        if (!in_array($video->object_name, ['interview_practice_s', 'application_s'])) {
            Log::warn('Not for summary video', $video->toArray());
            return null;
        }
       

        $output = $this->createOutput($video);
        //get all extra var required for loudnorm 
        $this->audioFilterVar = $this->ffmpeg->loudnormVar($video, $this->ipService->getQuestionByAnswer($video));
        
        // end here
        $this->buildFilterChains(
            $video,
            $this->ipService->getQuestionByAnswer($video),
            $output
        );
        //dd($this->ffmpeg->getCommandLine());
        Log::info('Executing ffmpeg command', ['command' => $this->ffmpeg->getCommandLine()]);

        $this->ffmpeg->run();

        $output->save();

        return $output;
    }

    public function getVideoData(MasterVideo $video)
    {
        $traits = DB::table('ibm_personality_api_result')
            ->where('transcript_id', '=', $video->transcript_id)
            ->get();

        $score = new \stdClass;
        $score->elev8 = $this->scoring->calculateElev8($traits);

        $session = $this->ipService->getSessionByAnswer($video);
        $score->name = str_replace("'", "'\\\''", $session->applicant_name);

        $traits_ = new \stdClass;
        $traits_->big5 = $this->scoring->getTopBig5Traits($traits);
        $traits_->nv = $this->scoring->getTopNVTraits($traits);

        $data = new \stdClass;
        $data->score = $score;
        $data->traits = $traits_;

        return $data;
    }

    public function calculateSegmentTiming($questionDuration, $answerDuration)
    {
        $segments = [
            'introView' => ['duration' => 1.77],
            'questionView' => ['duration' => $questionDuration],
            'overviewScroll' => ['duration' => 4],
            'overviewZoom' => ['duration' => 4],
            'scoreView' => ['duration' => 6],
            'traitsView' => ['duration' => 4],
            'outroView' => ['duration' => 3],
            'answerViews' => []
        ];

        $total = $segments['introView']['duration']
            + $segments['questionView']['duration']
            + $segments['overviewScroll']['duration']
            + $segments['overviewZoom']['duration']
            + $segments['scoreView']['duration']
            + $segments['traitsView']['duration'] * 3
            + $segments['outroView']['duration'];

        $ansSegs = [19, 15, 8, 8, 9]; // Segment durations
        $ansSegStartTime = [0];
        $ansSegStartTime[1] = $ansSegStartTime[0]
            + $ansSegs[0]
            + $segments['overviewScroll']['duration']
            + $segments['overviewZoom']['duration']
            + $segments['scoreView']['duration'];

        for ($i = 1; $i < 5; $i++) {
            $ansSegStartTime[$i + 1] = $ansSegStartTime[$i]
                + $ansSegs[$i]
                + $segments['traitsView']['duration'];
        }

        $i = 0;
        $d = $answerDuration;
        for ($i = 0; $i < count($ansSegs) && 2 < $d; $i++) {
            $segments['answerViews'][] = [
                'duration' => $ansSegs[$i],
                'startTime' => $ansSegStartTime[$i],
            ];
            $d -= $ansSegs[$i];
            $total += $ansSegs[$i];
        }

        $segments['total'] = $total;

        return $segments;
    }

    public function buildFilterChains($video, $questionClip, $output)
    {
        $this->companies = $this->generateRandomCompanies();

        $videoData = $this->getVideoData($video);
        $segments = $this->calculateSegmentTiming(
            $this->ffprobe->probe($questionClip->url)->duration,
            $this->ffprobe->probe($video->local_path)->duration
        );



        /******* Inputs ********/
        $mediaLib = config('app.media_lib');
        $inputs = (object) [
            'logoClip' => $this->ffmpeg->createInput($mediaLib['logo_clip']),
            'bgMusic' => $this->ffmpeg->createInput($mediaLib['bgm']),
            'logoImage' => $this->ffmpeg->createInput($mediaLib['elev8r_logo']),
            'splashImage' => $this->ffmpeg->createInput($mediaLib['splash'])
                ->addOption('loop', 1),
            'answerClip' => $this->ffmpeg->createInput($video->local_path),
            'questionClip' => $this->ffmpeg->createInput($questionClip->url)
                ->addOption('y')
                ->addOption('nostdin'),
            /*'profileImage' => $this->ffmpeg->createInput(
                $this->createResultScreenshotUrl($video)
            ),*/
        ];
        $inputs->outroClip = clone $inputs->logoClip;

        /******* Audio filters *******/
        $bgm = $this->ffmpeg->createAudioFilterchain()
            ->addInput($inputs->bgMusic)
            ->addFilter('volume', '0.2')
            ->trim($segments['total'])
            ->fadeOut($segments['total'] - 1)
            ->addOutput('bg_a');

        // WP-619: Normalize answer audio to question video
        $voiceOver = $this->voiceOverChain($inputs->answerClip, $segments);
        $questionVolume = $this->ffmpeg->detectVolume($questionClip->url);
        $answerVolume = $this->ffmpeg->detectVolume($video->local_path);
        $voiceOver->addFilter(
            'volume',
            [
                'volume' => ($questionVolume->max - $answerVolume->max) . 'dB',
                'precision' => 'fixed'
            ]
        );

        $mainAudio = $this->ffmpeg->createAudioFilterchain()
            ->addInput($inputs->logoClip)
            ->addInput($inputs->questionClip)
            ->addInput($voiceOver)
            ->addFilter('concat', ['n' => 3, 'a' => 1, 'v' => '0'])
            ->addOutput('main_a');

        $audio = $this->ffmpeg->createAudioFilterchain()
            ->addInput($mainAudio)
            ->addInput($bgm)
            ->addFilter('amix', ['duration' => 'longest'])
            ->addFilter('asetpts', 'PTS-STARTPTS')
            ->addOutput('a');

        $this->ffmpeg->addFilterchain($audio);

        /******* Video filters *******/
        $outroView = $this->ffmpeg->createVideoFilterchain()
            ->addInput($inputs->outroClip)
            ->addFilter('loop', '78:1:38')
            ->fadeIn(0, 0.25)
            ->addOutput('outro_v');

        $questionView = $this->questionViewChain(
            $inputs->questionClip,
            $segments['questionView']
        );

        /*$overviewScroll = $this->overviewScrollChain(
            $inputs->profileImage,
            $segments['overviewScroll']
        );*/

        /*$overviewZoom = $this->overviewZoomChain(
            $inputs->profileImage,
            $segments['overviewZoom']
        );*/

        $scoreView = $this->scoreViewChain(
            $inputs->splashImage,
            $segments['scoreView'],
            $videoData->score
        );

        $answerViews = [];
        $answerViewsCount = count($segments['answerViews']);
        for ($i = 0; $i < $answerViewsCount; $i++) {
            $view = $segments['answerViews'][$i];
            $answerViews[$i] = $this->ffmpeg->createVideoFilterchain()
                ->addInput($inputs->answerClip)
                ->trim(
                    $view['duration'],
                    $view['startTime']
                )
                ->addFilter('setpts', 'PTS-STARTPTS')
                ->addCustomString("split=2[answer_view".$i."_v_scale1][answer_view".$i."_v_scale2];")
                ->addCustomString("[answer_view".$i."_v_scale1]scale=1280:720,setsar=1,boxblur=20:20[answer_view".$i."_v_scaled1];")
                ->addCustomString("[answer_view".$i."_v_scale2]scale=1280:720:force_original_aspect_ratio=decrease[answer_view".$i."_v_scaled2];")
                ->addCustomString("[answer_view".$i."_v_scaled1][answer_view".$i."_v_scaled2]overlay=x='(W-w)/2':y='(H-h)/2'")
                ->addOutput("answer_view{$i}_v");
        }
        $traitsViews = $this->traitsViewChains(
            $inputs->splashImage,
            $segments['traitsView'],
            $videoData
        );

        $mainVideo = $this->ffmpeg->createVideoFilterchain()
            ->addInput($inputs->logoClip)
            ->addInput($questionView)
            ->addInput($answerViews[0])
            //->addInput($overviewScroll)
            //->addInput($overviewZoom)
            ->addInput($scoreView);

        if ($answerViewsCount > 1) {
            $mainVideo->addInput($answerViews[1]);
        }

        $mainVideo->addInput($traitsViews['big5_openness']);
        if ($answerViewsCount > 2) {
            $mainVideo->addInput($answerViews[2]);
        }

        $mainVideo->addInput($traitsViews['big5_extraversion']);
        if ($answerViewsCount > 3) {
            $mainVideo->addInput($answerViews[3]);
        }

        $mainVideo->addInput($traitsViews['needs_values']);
        if ($answerViewsCount > 4) {
            $mainVideo->addInput($answerViews[4]);
        }

        $mainVideo->addInput($outroView)
            ->addFilter('concat', ['v' => 1, 'n' => (9 + $answerViewsCount)])
            ->addOutput('main_v');

        $watermark = $this->ffmpeg->createVideoFilterchain()
            ->addInput($inputs->logoImage)
            ->addFilter('scale', [
                'w' => '0.414*iw',
                'h' => '0.414*ih'
            ])
            ->addOutput('logo_i');

        $videoResult = $this->ffmpeg->createVideoFilterchain()
            ->addInput($mainVideo)
            ->addInput($watermark)
            ->addFilter('overlay', [
                'x' => '0',
                'y' => '(main_h-overlay_h)'
            ])
            ->addOutput('v');

        foreach ($answerViews as $key => $chain) {
            $this->ffmpeg->addFilterchain($chain);
        }

        foreach ($traitsViews as $key => $chain) {
            $this->ffmpeg->addFilterchain($chain);
        }

        $this->ffmpeg->addFilterchain($videoResult);

        $this->ffmpeg->addOutput(
            $this->ffmpeg->createOutput($output->local_path)
                ->addMap($audio)
                ->addMap($videoResult)
                ->addOption('pix_fmt', 'yuv420p')
        );
    }

    protected function questionViewChain($input, $segment)
    {
        return $this->ffmpeg->createVideoFilterchain()
            ->addInput($input)
            ->fadeOut($segment['duration'] - 1)
            ->addOutput('question_v');
    }

    protected function overviewScrollChain($input, $segment)
    {
        $duration = $segment['duration'];
        $half = $duration / 2 + 1;

        return $this->ffmpeg->createVideoFilterchain()
            ->addInput($input)
            ->addFilter('loop', ($duration * 25) . ':1')
            ->fadeIn()
            ->trim($duration)
            ->addFilter('crop', [
                'w' => 'iw',
                'h' => '720',
                'x' => '0',
                'y' => "'if(gte($half, t),
                    t*(ih-oh)/" . ($half - 1) . ",
                    (ih-oh) - (t-$half)*(ih-oh))'"
            ])
            ->addFilter('scale', '1280x720')
            ->addOutput('overview_scroll_v');
    }

    protected function overviewZoomChain($input, $segment)
    {
        $duration = $segment['duration'];
        return $this->ffmpeg->createVideoFilterchain()
            ->addInput($input)
            ->addFilter('crop', [
                'w' => 'iw',
                'h' => 720,
                'x' => 0,
                'y' => 0
            ])
            ->addFilter('zoompan', [
                'd' => $duration * 25,
                'x' => "'iw/2-747-(iw/zoom/2)'",
                'y' => "'trunc(927-ih/zoom)'",
                'z' => "'if(lte(on, 100), min(zoom+0.025, 2), max(1, zoom-0.025))'"
            ])
            ->fadeOut($duration - 0.5, 0.5)
            ->addOutput('overview_zoom_v');
    }

    protected function scoreViewChain($input, $segment, $score)
    {
        $fonts = config('app.media_lib.fonts');

        $text = $this->breakLine(
            "Hey, {$this->companies[0]}, {$score->name} shares a
            similar Elev8 score with current employees and could be a great fit.",
            1152,
            30,
            $fonts['text']
        );

        $offset = (count($text) * 32) / 2;

        $chain = $this->ffmpeg->createVideoFilterchain()
            ->addInput($input)
            ->trim($segment['duration'])
            ->addFilter('setpts', 'PTS-STARTPTS')
            ->addFilter('drawtext', [
                'fontsize' => 48,
                'fontcolor' => '#0070C0',
                'x' => '(w-text_w)/2',
                'y' => '(h-text_h)/2-' . $offset,
                'fontfile' => "'{$fonts['title']}'",
                'text' => "'{$score->name} has an Elev8 Score of {$score->elev8}.'"
            ]);

        $offset -= 20;
        foreach ($text as $t) {
            $offset -= 32;
            $chain->addFilter('drawtext', [
                'x' => '(w-text_w)/2',
                'y' => '(h-text_h)/2-' . $offset,
                'fontsize' => 30,
                'fontcolor' => 'white',
                'fontfile' => "'{$fonts['text']}'",
                'text' => "'{$t}'"
            ]);
        }

        $chain->fadeOut($segment['duration'] - 1)
            ->addOutput('splash_score_v');

        return $chain;
    }

    protected function traitsViewChains($input, $segment, $data)
    {
        $traits = $data->traits;

        $data = [
            'big5_openness' => [
                'title' => 'Best Openness Trait',
                'subtitle' =>"That means that {$data->score->name} is also a
                    great fit with {$this->companies[1]} as many employees share
                    the same high level of {$traits->big5['big5_openness']->name}.",
                'traits' => [$traits->big5['big5_openness']]
            ],
            'big5_extraversion' => [
                'title' => 'Best Warmness Trait',
                'subtitle' => "That'\\\''s great for {$this->companies[2]}
                    as {$data->score->name} could be a great fit. Current
                    employees share a similar high value for this behavior.",
                'traits' => [$traits->big5['big5_extraversion']]
            ],
            'needs_values' => [
                'title' => 'Best Needs and Values Trait',
                'subtitle' => "That means that {$data->score->name} is also a great fit
                    with {$this->companies[1]} as many employees share the same elevated
                    level of {$traits->big5['big5_openness']->name}.",
                'traits' => [
                    $traits->nv['needs'],
                    $traits->nv['values']
                ]
            ],
        ];

        $duration = $segment['duration'];

        $chains = [
            'bg' => $this->ffmpeg->createVideoFilterchain()
                ->addInput($input)
                ->trim($duration)
                ->addFilter('setpts', 'PTS-STARTPTS')
                ->addFilter('split', 3)
        ];

        $fontFile = config('app.media_lib.fonts');

        foreach ($data as $key => $score) {
            $chains['bg']->addOutput("tt_{$key}_bg_v");

            $offset = count($score['traits']) * 44 + 32;
            $offset /= 2;

            $chain = $this->ffmpeg->createVideoFilterchain()
                ->addInput("tt_{$key}_bg_v")
                ->trim($duration)
                ->addFilter('setpts', 'PTS-STARTPTS')
                ->addFilter('drawtext', [
                    'x' => '(w-text_w)/2',
                    'y' => '(h-text_h)/2-' . $offset,
                    'fontsize' => 48,
                    'fontcolor' => '#0070C0',
                    'fontfile' => "{$fontFile['title']}",
                    'text' => "'{$score['title']}'"
                ]);

            $offset -= 20;
            foreach ($score['traits'] as $trait) {
                $offset -= 44;
                $percentile = sprintf('%d', $trait->percentile * 100);
                $chain->addFilter('drawtext', [
                    'x' => '(w-text_w)/2',
                    'y' => '(h-text_h)/2-' . $offset,
                    'fontsize' => 34,
                    'fontcolor' => '#0070C0',
                    'fontfile' => "{$fontFile['subtitle']}",
                    'text' => "'-{$trait->name} {$percentile}th Percentile'"
                ]);
            }

            $offset -= 40;
            $text = $this->breakLine(
                $score['subtitle'],
                1152,
                30,
                $fontFile['text']
            );
            foreach ($text as $sub) {
                $offset -= 32;
                $chain->addFilter('drawtext', [
                    'x' => '(w-text_w)/2',
                    'y' => '(h-text_h)/2-' . $offset,
                    'fontsize' => 30,
                    'fontcolor' => 'white',
                    'fontfile' => "'{$fontFile['text']}'",
                    'text' => "'{$sub}'"
                ]);
            }

            $chain->fadeOut($duration - 1)
                ->addOutput("tt_{$key}_v");

            $chains[$key] = $chain;
        }

        return $chains;
    }

    protected function voiceOverChain($input, $segments)
    {
        $duration = $segments['total']
            - $segments['questionView']['duration']
            - $segments['introView']['duration'];
        
        return $this->ffmpeg->createAudioFilterchain()
            ->addInput($input)
            ->addFilter('agate')
            ->addFilter('loudnorm',[
                'i' => $this->audioFilterVar['question']['input_i'], 
                'tp' => 0,      //$this->audioFilterVar['question']['input_tp'] set static 0 as per discussion with brian
                'lra' => $this->audioFilterVar['question']['input_lra'],
                'linear' => true,
                'dual_mono' => true,
                'measured_i' => $this->audioFilterVar['answer']['input_i'],
                'measured_tp' => $this->audioFilterVar['answer']['input_tp'],
                'measured_lra' => $this->audioFilterVar['answer']['input_lra'],
                'measured_thresh' => $this->audioFilterVar['answer']['input_thresh'],
                'offset' => $this->audioFilterVar['answer']['target_offset'],
            ])->trim($duration)
            ->fadeOut($duration - 5, 5)
            ->addOutput('vo_a');
    }

    
    public function createResultScreenshotUrl(MasterVideo $video)
    {
        $options = [];
        $options['width'] = 1280;
        $options['delay'] = 5000;
        $options['selector'] = '.new-modal';

        $matches = [];

        $options['url'] = $this->ipService->createResultUrlByAnswer($video);
        // $url = $this->ipService->createResultUrlByAnswer($video);
        // if (preg_match('/[%a-zA-Z0-9]+$/', $url, $matches)) {
        //     $options['url'] = str_replace($matches[0], urlencode($matches[0]), $url);
        // } else {
        //     $options['url'] = $url;
        // }
        Log::info('checking urlbox.is call');
        return Urlbox::generateUrl($options);
    }

    public function createOutput(MasterVideo $video)
    {
        $path = pathinfo($video->local_path);

        $newPath = $path['dirname'] . '/summary_p.mp4';
        $newUrl = str_replace(
            $path['filename'] . '.' . $path['extension'],
            'summary_p.mp4',
            $video->local_url
        );

        $model = new MasterVideo();
        $model->transcript_id = $video->transcript_id;
        $model->object_id = $video->object_id;
        $model->object_name = 'summary';
        $model->local_path = $newPath;
        $model->local_url = $newUrl;

        return $model;
    }

    public function generateRandomCompanies($num = 3)
    {
        $companies = [];
        $file = new \SplFileObject(resource_path('data/companies.csv'));

        for ($i = 0; $i < $num; $i++) {
            $file->seek(rand(0, 997));
            $companies[$i] = str_replace("'", "'\\''", trim($file->current()));
            $file->rewind();
        }

        return $companies;
    }

    public function wordWrap($text, $width, $size, $font)
    {
        # Check if imagettfbbox is expecting font-size to be declared in points or pixels.
        static $mult;
        $mult = $mult ?: version_compare(GD_VERSION, '2.0', '>=') ? .75 : 1;

        $box = imagettfbbox($size * $mult, 0, $font, $text);

        # Text already fits the designated space without wrapping.
        if ($box[2] - $box[0] / $mult < $width) {
            return $text;
        }

        # Start measuring each line of our input and inject line-breaks when overflow's detected.
        $output = '';
        $length = 0;

        $words = preg_split('/\b(?=\S)|(?=\s)/', $text);
        $wordCount = count($words);

        for ($i = 0; $i < $wordCount; ++$i) {
            # Newline
            if (PHP_EOL === $words[$i]) {
                $length = 0;
            }

            # Strip any leading tabs.
            if (!$length) {
                $words[$i] = preg_replace('/^\t+/', '', $words[$i]);
            }

            $box = imagettfbbox($size * $mult, 0, $font, $words[$i]);
            $m = $box[2] - $box[0] / $mult;

            # This is a long word, so try to hyphenate it.
            if (($diff = $width - $m) <= 0) {
                $diff = abs($diff);

                # Figure out which end of the word to start measuring from.
                # Saves a few extra cycles in an already heavy-duty function.
                if ($diff - $width <= 0) {
                    for ($s = strlen($words[$i]); $s; --$s) {
                        $box = imagettfbbox($size * $mult, 0, $font, substr($words[$i], 0, $s) . '-');

                        if ($width > ($box[2] - $box[0] / $mult) + $size) {
                            $breakpoint = $s;
                            break;
                        }
                    }
                } else {
                    $wordLength = strlen($words[$i]);
                    for ($s = 0; $s < $wordLength; ++$s) {
                        $box = imagettfbbox($size * $mult, 0, $font, substr($words[$i], 0, $s+1) . '-');
                        if ($width < ($box[2] - $box[0] / $mult) + $size) {
                            $breakpoint = $s;
                            break;
                        }
                    }
                }

                if ($breakpoint) {
                    $w_l = substr($words[$i], 0, $s + 1) . '-';
                    $w_r = substr($words[$i], $s + 1);

                    $words[$i] = $w_l;
                    array_splice($words, $i+1, 0, $w_r);
                    ++$wordCount;
                    $box = imagettfbbox($size * $mult, 0, $font, $w_l);
                    $m = $box[2] - $box[0] / $mult;
                }
            }

            # If there's no more room on the current line to fit the next word, start a new line.
            if ($length > 0 && $length + $m >= $width) {
                $output .= PHP_EOL;
                $length = 0;

                # If the current word is just a space, don't bother. Skip (saves a weird-looking gap in the text).
                if (' ' === $words[$i]) {
                    continue;
                }
            }

            # Write another word and increase the total length of the current line.
            $output .= $words[$i];
            $length += $m;
        }

        return $output;
    }

    public function breakLine($text, $width, $size, $font)
    {
        $onelineText = preg_replace("/\s+/", ' ', $text);
        $wrappedText = $this->wordwrap(
            $onelineText,
            $width,
            $size,
            $font
        );
        return explode("\n", $wrappedText);
    }
}
