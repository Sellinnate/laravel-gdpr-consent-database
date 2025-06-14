<?php

namespace Selli\LaravelGdprConsentDatabase\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Selli\LaravelGdprConsentDatabase\Models\ConsentType;
use Selli\LaravelGdprConsentDatabase\Services\GuestConsentManager;

class GuestConsentController extends Controller
{
    protected $guestConsentManager;

    public function __construct(GuestConsentManager $guestConsentManager)
    {
        $this->guestConsentManager = $guestConsentManager;
    }

    public function acceptAll(Request $request)
    {
        $technicalCookieCode = $request->input('technical_cookie_code') ?: $request->cookie('gdpr_session_id');
        $consentTypes = ConsentType::where('active', true)->where('category', 'cookie')->get();

        foreach ($consentTypes as $consentType) {
            $this->guestConsentManager->giveConsent($consentType->slug, [
                'source' => 'cookie_banner',
                'action' => 'accept_all',
            ], null, $technicalCookieCode);
        }

        return response()->json(['success' => true]);
    }

    public function rejectAll(Request $request)
    {
        $technicalCookieCode = $request->input('technical_cookie_code') ?: $request->cookie('gdpr_session_id');
        $requiredConsentTypes = ConsentType::where('active', true)
            ->where('required', true)
            ->where('category', 'cookie')
            ->get();

        foreach ($requiredConsentTypes as $consentType) {
            $this->guestConsentManager->giveConsent($consentType->slug, [
                'source' => 'cookie_banner',
                'action' => 'reject_all',
            ], null, $technicalCookieCode);
        }

        return response()->json(['success' => true]);
    }

    public function savePreferences(Request $request)
    {
        $technicalCookieCode = $request->input('technical_cookie_code') ?: $request->cookie('gdpr_session_id');
        $consents = $request->input('consents', []);

        foreach ($consents as $slug => $granted) {
            if ($granted) {
                $this->guestConsentManager->giveConsent($slug, [
                    'source' => 'cookie_banner',
                    'action' => 'save_preferences',
                ], null, $technicalCookieCode);
            } else {
                $this->guestConsentManager->revokeConsent($slug, $technicalCookieCode);
            }
        }

        return response()->json([
            'success' => true,
            'consents' => $consents,
        ]);
    }

    public function getConsentStatus(Request $request)
    {
        $technicalCookieCode = $request->input('technical_cookie_code') ?: $request->cookie('gdpr_session_id');
        $consentTypes = ConsentType::where('active', true)->where('category', 'cookie')->get();
        $hasAnyConsent = false;
        $consentStatus = [];

        foreach ($consentTypes as $consentType) {
            $hasConsent = $this->guestConsentManager->hasConsent($consentType->slug, $technicalCookieCode);
            $consentStatus[$consentType->slug] = $hasConsent;
            if ($hasConsent) {
                $hasAnyConsent = true;
            }
        }

        return response()->json([
            'hasAnyConsent' => $hasAnyConsent,
            'consents' => $consentStatus,
            'consentTypes' => $consentTypes,
        ]);
    }
}
