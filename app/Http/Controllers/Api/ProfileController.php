<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CandidateProfile;
use App\Models\CandidateSkill;
use App\Models\CandidateExperience;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $profile = $request->user()
            ->candidateProfile()
            ->with(['skills', 'experiences'])
            ->firstOrCreate(['user_id' => $request->user()->id], [
                'full_name' => $request->user()->name,
            ]);

        return response()->json($profile);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'full_name'             => 'sometimes|string|max:255',
            'primary_title'         => 'sometimes|nullable|string|max:255',
            'location'              => 'sometimes|nullable|string|max:255',
            'portfolio_url'         => 'sometimes|nullable|url|max:500',
            'summary'               => 'sometimes|nullable|string|max:2000',
            'work_preference'       => 'sometimes|in:remote,hybrid,onsite,any',
            'minimum_salary'        => 'sometimes|nullable|numeric|min:0',
            'years_of_experience'   => 'sometimes|nullable|integer|min:0|max:50',
            'preferred_roles'       => 'sometimes|nullable|array',
            'preferred_roles.*'     => 'string',
            'preferred_locations'   => 'sometimes|nullable|array',
            'preferred_locations.*' => 'string',
            'preferred_currencies'  => 'sometimes|nullable|array',
            'preferred_currencies.*'=> 'string',
            'preferred_industries'  => 'sometimes|nullable|array',
            'preferred_industries.*'=> 'string',
            'excluded_industries'   => 'sometimes|nullable|array',
            'excluded_industries.*' => 'string',
            'preferred_technologies'  => 'sometimes|nullable|array',
            'preferred_technologies.*'=> 'string',

            // Skills
            'skills'       => 'sometimes|array',
            'skills.*.skill' => 'required_with:skills|string|max:100',
            'skills.*.level' => 'sometimes|in:beginner,intermediate,advanced,expert',
            'skills.*.years' => 'sometimes|nullable|integer|min:0',

            // Experience
            'experiences'              => 'sometimes|array',
            'experiences.*.company'    => 'required_with:experiences|string|max:255',
            'experiences.*.title'      => 'required_with:experiences|string|max:255',
            'experiences.*.description'=> 'sometimes|nullable|string',
            'experiences.*.start_date' => 'sometimes|nullable|date',
            'experiences.*.end_date'   => 'sometimes|nullable|date',
            'experiences.*.is_current' => 'sometimes|boolean',
        ]);

        $profile = $request->user()
            ->candidateProfile()
            ->firstOrCreate(['user_id' => $request->user()->id], [
                'full_name' => $request->user()->name,
            ]);

        // Update main profile fields
        $profileFields = array_diff_key($data, array_flip(['skills', 'experiences']));
        $profile->fill($profileFields)->save();

        // Sync skills
        if (isset($data['skills'])) {
            $profile->skills()->delete();
            foreach ($data['skills'] as $skill) {
                $profile->skills()->create($skill);
            }
        }

        // Sync experiences
        if (isset($data['experiences'])) {
            $profile->experiences()->delete();
            foreach ($data['experiences'] as $i => $exp) {
                $profile->experiences()->create(array_merge($exp, ['sort_order' => $i]));
            }
        }

        return response()->json($profile->load(['skills', 'experiences']));
    }

    public function uploadCv(Request $request): JsonResponse
    {
        $request->validate([
            'cv' => 'required|file|mimes:pdf|max:5120', // 5MB max
        ]);

        $profile = $request->user()->candidateProfile;
        if (!$profile) {
            return response()->json(['message' => 'Profile not found.'], 404);
        }

        // Delete old CV
        if ($profile->cv_path) {
            Storage::disk('local')->delete($profile->cv_path);
        }

        $path = $request->file('cv')->store("cvs/{$request->user()->id}", 'local');
        $profile->update(['cv_path' => $path]);

        return response()->json([
            'message' => 'CV uploaded successfully.',
            'cv_path' => $path,
        ]);
    }
}
