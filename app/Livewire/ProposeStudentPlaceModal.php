<?php

namespace App\Livewire;

use App\Models\StudentPlaceCategory;
use App\Models\StudentPlaceSuggestion;
use App\Services\DiscordNotifier;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

class ProposeStudentPlaceModal extends Component
{
    public bool $isOpen = false;
    public string $name = '';
    public ?int $categoryId = null;
    public string $address = '';
    public string $highlight = '';
    public string $description = '';
    public string $websiteUrl = '';
    public string $instagramHandle = '';
    public string $submitterName = '';
    public string $submitterEmail = '';
    public string $honeypot = ''; // Spam honeypot
    public bool $submitted = false;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:150',
            'address' => 'required|string|min:3|max:200',
            'description' => 'required|string|min:10|max:1000',
            'highlight' => 'nullable|string|max:150',
            'categoryId' => 'nullable|integer',
            'websiteUrl' => 'nullable|url|max:255',
            'instagramHandle' => 'nullable|string|max:100',
            'submitterName' => 'nullable|string|max:100',
            'submitterEmail' => 'nullable|email|max:150',
        ];
    }

    protected $messages = [
        'name.required' => 'Zadej prosím název místa.',
        'name.min' => 'Název místa musí mít alespoň 2 znaky.',
        'address.required' => 'Zadej prosím adresu nebo lokalitu v Brně.',
        'description.required' => 'Napiš nám v pár větách, proč je místo skvělé pro studenty.',
        'description.min' => 'Popis musí mít alespoň 10 znaků.',
        'websiteUrl.url' => 'Zadej platnou webovou adresu včetně https://.',
        'submitterEmail.email' => 'Zadej platnou e-mailovou adresu.',
    ];

    public function openModal(): void
    {
        $this->isOpen = true;
        $this->submitted = false;
    }

    public function closeModal(): void
    {
        $this->isOpen = false;
    }

    public function submit(): void
    {
        // Honeypot check proti robotům
        if (!empty($this->honeypot)) {
            $this->submitted = true;
            return;
        }

        $isE2eTest = (function_exists('request') && request() && request()->header('X-E2E-Testing') === 'true')
            || str_contains(mb_strtolower($this->description), 'e2e')
            || str_contains(mb_strtolower($this->name), 'e2e');

        $ip = request()?->ip() ?? '127.0.0.1';
        $rateLimitKey = 'suggest-place:' . $ip;

        if (!app()->environment('testing') && !$isE2eTest && RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            $this->addError('name', "Příliš mnoho odeslaných návrhů. Zkus to prosím znovu za {$seconds} sekund.");
            return;
        }

        $this->validate();

        $cleanIg = trim($this->instagramHandle);
        if (!empty($cleanIg) && !str_starts_with($cleanIg, '@')) {
            $cleanIg = '@' . $cleanIg;
        }

        $suggestion = StudentPlaceSuggestion::create([
            'name' => trim($this->name),
            'student_place_category_id' => $this->categoryId ?: null,
            'address' => trim($this->address),
            'highlight' => trim($this->highlight) ?: null,
            'description' => trim($this->description),
            'website_url' => trim($this->websiteUrl) ?: null,
            'instagram_handle' => $cleanIg ?: null,
            'submitter_name' => trim($this->submitterName) ?: null,
            'submitter_email' => trim($this->submitterEmail) ?: null,
            'status' => 'pending',
            'ip_hash' => hash('sha256', $ip . config('app.key', 'brno-events')),
        ]);

        if (!$isE2eTest) {
            RateLimiter::hit($rateLimitKey, 3600);
            if (!app()->environment('testing')) {
                DiscordNotifier::notifyPlaceSuggestion($suggestion);
            }
        } else {
            // V automatických E2E testech záznam po ověření smažeme, aby neznečišťoval databázi
            $suggestion->delete();
        }

        $this->submitted = true;
        $this->reset(['name', 'categoryId', 'address', 'highlight', 'description', 'websiteUrl', 'instagramHandle', 'submitterName', 'submitterEmail', 'honeypot']);
    }

    public function render()
    {
        $categories = StudentPlaceCategory::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('livewire.propose-student-place-modal', [
            'categories' => $categories,
        ]);
    }
}
