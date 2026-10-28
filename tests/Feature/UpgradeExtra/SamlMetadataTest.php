<?php

namespace Tests\Feature\UpgradeExtra;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Tests\Feature\Upgrade\UpgradeTestCase;

/**
 * The SAML integration has to keep working after the upgrade of
 * onelogin/php-saml, in particular the SP metadata of the service provider.
 */
class SamlMetadataTest extends UpgradeTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // the saml routes are registered by the SamlServiceProvider
        $this->enableSaml();
    }

    /**
     * A self signed certificate and its private key.
     *
     * @return array<string, string>
     */
    protected function keyPair(): array
    {
        $dn = [
            'countryName' => 'US',
            'stateOrProvinceName' => 'N/A',
            'localityName' => 'N/A',
            'organizationName' => 'Snipe-IT',
            'commonName' => 'Snipe-IT',
        ];

        $key = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        $csr = openssl_csr_new($dn, $key, ['digest_alg' => 'sha256']);
        $x509 = openssl_csr_sign($csr, null, $key, 3650, ['digest_alg' => 'sha256']);

        $certificate = '';
        $privateKey = '';
        openssl_x509_export($x509, $certificate);
        openssl_pkey_export($key, $privateKey);

        return ['certificate' => $certificate, 'privateKey' => $privateKey];
    }

    /**
     * The metadata of a minimal identity provider.
     */
    protected function idpMetadata(string $certificate): string
    {
        $base64 = preg_replace('/-----[A-Z ]+-----|\s+/', '', $certificate);

        return <<<XML
<?xml version="1.0"?>
<EntityDescriptor xmlns="urn:oasis:names:tc:SAML:2.0:metadata" entityID="https://idp.example.test/metadata">
    <IDPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
        <KeyDescriptor use="signing">
            <ds:KeyInfo xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
                <ds:X509Data>
                    <ds:X509Certificate>{$base64}</ds:X509Certificate>
                </ds:X509Data>
            </ds:KeyInfo>
        </KeyDescriptor>
        <SingleSignOnService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" Location="https://idp.example.test/sso"/>
    </IDPSSODescriptor>
</EntityDescriptor>
XML;
    }

    protected function enableSaml(): void
    {
        $settings = Setting::first();

        if (! $settings) {
            $settings = new Setting();
            $settings->per_page = 20;
            $settings->site_name = 'Snipe-IT';
            $settings->pwd_secure_min = 8;
        }

        $idp = $this->keyPair();
        $serviceProvider = $this->keyPair();

        $settings->saml_enabled = 1;
        $settings->saml_idp_metadata = $this->idpMetadata($idp['certificate']);
        $settings->saml_sp_x509cert = $serviceProvider['certificate'];
        $settings->saml_sp_privatekey = $serviceProvider['privateKey'];
        $settings->saml_sp_x509certNew = '';
        $settings->save();

        Cache::forget(Setting::APP_SETTINGS_KEY);
    }

    public function testTheMetadataRouteReturnsValidXml(): void
    {
        $response = $this->get('/saml/metadata');

        $response->assertStatus(200);

        $xml = $response->streamedContent();

        $this->assertNotEmpty($xml);

        $previous = libxml_use_internal_errors(true);
        $document = simplexml_load_string($xml);
        libxml_use_internal_errors($previous);

        $this->assertNotFalse($document, 'the metadata is not valid XML');
        $this->assertStringContainsString('EntityDescriptor', $xml);
        $this->assertStringContainsString('AssertionConsumerService', $xml);
        $this->assertStringContainsString('/saml/acs', $xml);
        $this->assertStringContainsString('/saml/sls', $xml);
    }

    public function testTheSamlServiceIsEnabledWithTheSettings(): void
    {
        $saml = app(\App\Services\Saml::class);

        $this->assertTrue($saml->isEnabled());
    }
}
