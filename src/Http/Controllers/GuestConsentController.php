<?php

declare(strict_types=1);

namespace Selli\LaravelGdprConsentDatabase\Http\Controllers;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Selli\LaravelGdprConsentDatabase\Models\ConsentType;
use Selli\LaravelGdprConsentDatabase\Services\GuestConsentManager;

class GuestConsentController extends Controller
{
    public function __construct(
        protected GuestConsentManager $guestConsentManager
    ) {}

    /**
     * Grant every active cookie consent type for the current visitor.
     */
    public function acceptAll(Request $request): JsonResponse
    {
        $technicalCookieCode = $this->resolveTechnicalCookieCode($request);

        foreach ($this->activeCookieConsentTypes() as $consentType) {
            $this->guestConsentManager->giveConsent($consentType->slug, [
                'source' => 'cookie_banner',
                'action' => 'accept_all',
            ], null, $technicalCookieCode);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Reject all optional cookie consents while keeping the required ones.
     *
     * Required cookie types are granted (they are strictly necessary); every optional cookie
     * consent the visitor may have granted before is explicitly revoked.
     */
    public function rejectAll(Request $request): JsonResponse
    {
        $technicalCookieCode = $this->resolveTechnicalCookieCode($request);

        foreach ($this->activeCookieConsentTypes() as $consentType) {
            if ($consentType->required) {
                $this->guestConsentManager->giveConsent($consentType->slug, [
                    'source' => 'cookie_banner',
                    'action' => 'reject_all',
                ], null, $technicalCookieCode);
            } else {
                $this->guestConsentManager->revokeConsent($consentType->slug, $technicalCookieCode);
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * Persist a granular set of cookie preferences for the current visitor.
     */
    public function savePreferences(Request $request): JsonResponse
    {
        $request->validate([
            'consents' => ['array'],
            'consents.*' => ['boolean'],
            'technical_cookie_code' => ['nullable', 'string'],
        ]);

        $technicalCookieCode = $this->resolveTechnicalCookieCode($request);
        $consents = $request->input('consents', []);
        $consents = is_array($consents) ? $consents : [];

        // Only act on active cookie-category types; ignore any other (or unknown) slug the client
        // sends, so the banner cannot fabricate consents for non-cookie purposes or crash on a
        // retired/unknown slug.
        $allowed = $this->activeCookieConsentTypes()->keyBy('slug');
        $applied = [];

        foreach ($consents as $slug => $granted) {
            if (! $allowed->has($slug)) {
                continue;
            }

            if ($granted) {
                $this->guestConsentManager->giveConsent((string) $slug, [
                    'source' => 'cookie_banner',
                    'action' => 'save_preferences',
                ], null, $technicalCookieCode);
            } else {
                $this->guestConsentManager->revokeConsent((string) $slug, $technicalCookieCode);
            }

            $applied[$slug] = (bool) $granted;
        }

        return response()->json([
            'success' => true,
            'consents' => $applied,
        ]);
    }

    /**
     * Return the current consent status for every active cookie consent type.
     */
    public function getConsentStatus(Request $request): JsonResponse
    {
        $technicalCookieCode = $this->resolveTechnicalCookieCode($request);
        $consentTypes = $this->activeCookieConsentTypes();

        $consentStatus = [];
        $hasAnyConsent = false;

        foreach ($consentTypes as $consentType) {
            $hasConsent = $this->guestConsentManager->hasConsent($consentType->slug, $technicalCookieCode);
            $consentStatus[$consentType->slug] = $hasConsent;
            $hasAnyConsent = $hasAnyConsent || $hasConsent;
        }

        return response()->json([
            'hasAnyConsent' => $hasAnyConsent,
            'consents' => $consentStatus,
            // Only expose presentation fields — never the internal compliance metadata
            // (legal_basis, data_controller, policy_text_hash, …) to anonymous visitors.
            'consentTypes' => $consentTypes->map(fn (ConsentType $type): array => [
                'slug' => $type->slug,
                'name' => $type->name,
                'description' => $type->description,
                'required' => $type->required,
            ])->values(),
        ]);
    }

    /**
     * Resolve the technical cookie code from the request payload or the session cookie.
     */
    protected function resolveTechnicalCookieCode(Request $request): ?string
    {
        $code = $request->input('technical_cookie_code') ?: $request->cookie('gdpr_session_id');

        return is_string($code) ? $code : null;
    }

    /**
     * Get every active consent type that belongs to the cookie category.
     *
     * @return Collection<int, ConsentType>
     */
    protected function activeCookieConsentTypes(): Collection
    {
        return ConsentType::query()
            ->where('active', true)
            ->where('category', 'cookie')
            ->get();
    }
}
