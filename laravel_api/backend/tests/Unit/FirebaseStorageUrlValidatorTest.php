<?php

namespace Tests\Unit;

use App\Services\FirebaseStorageUrlValidator;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class FirebaseStorageUrlValidatorTest extends TestCase
{
    private const TEST_BUCKET = 'titeducation-ff12f.firebasestorage.app';

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.firebase.web.storageBucket', self::TEST_BUCKET);
    }

    public function test_accepts_valid_url_with_correct_bucket_and_institute(): void
    {
        $url = 'https://firebasestorage.googleapis.com/v0/b/' . self::TEST_BUCKET . '/o/learning-suite%2Fi1%2Fnotes%2Fgrade-4%2Fdoc.pdf?alt=media&token=xyz';

        $this->assertTrue(FirebaseStorageUrlValidator::isValidForInstitute($url, 1));
    }

    public function test_rejects_foreign_bucket(): void
    {
        $url = 'https://firebasestorage.googleapis.com/v0/b/FOREIGN-PROJECT.appspot.com/o/learning-suite%2Fi1%2Fnotes%2Fdoc.pdf?alt=media';

        $this->assertFalse(FirebaseStorageUrlValidator::isValidForInstitute($url, 1));
    }

    public function test_rejects_marker_only_in_query_string(): void
    {
        $url = 'https://firebasestorage.googleapis.com/v0/b/' . self::TEST_BUCKET . '/o/anything.pdf?alt=media&x=learning-suite/i1/';

        $this->assertFalse(FirebaseStorageUrlValidator::isValidForInstitute($url, 1));
    }

    public function test_rejects_when_bucket_config_empty(): void
    {
        Config::set('services.firebase.web.storageBucket', '');
        $url = 'https://firebasestorage.googleapis.com/v0/b/' . self::TEST_BUCKET . '/o/learning-suite%2Fi1%2Fnotes%2Fdoc.pdf?alt=media';

        $this->assertFalse(FirebaseStorageUrlValidator::isValidForInstitute($url, 1));

        Config::set('services.firebase.web.storageBucket', null);
        $this->assertFalse(FirebaseStorageUrlValidator::isValidForInstitute($url, 1));
    }

    public function test_rejects_different_institute_id(): void
    {
        $url = 'https://firebasestorage.googleapis.com/v0/b/' . self::TEST_BUCKET . '/o/learning-suite%2Fi2%2Fnotes%2Fdoc.pdf?alt=media';

        $this->assertFalse(FirebaseStorageUrlValidator::isValidForInstitute($url, 1));
    }

    public function test_rejects_path_traversal_in_object_path(): void
    {
        $url = 'https://firebasestorage.googleapis.com/v0/b/' . self::TEST_BUCKET . '/o/learning-suite%2Fi1%2F..%2Fsecret.pdf?alt=media';

        $this->assertFalse(FirebaseStorageUrlValidator::isValidForInstitute($url, 1));
    }

    public function test_rejects_non_https_scheme(): void
    {
        $url = 'http://firebasestorage.googleapis.com/v0/b/' . self::TEST_BUCKET . '/o/learning-suite%2Fi1%2Fnotes%2Fdoc.pdf?alt=media';

        $this->assertFalse(FirebaseStorageUrlValidator::isValidForInstitute($url, 1));
    }

    public function test_rejects_foreign_host(): void
    {
        $url = 'https://firebasestorage.googleapis.com.attacker.com/v0/b/' . self::TEST_BUCKET . '/o/learning-suite%2Fi1%2Fnotes%2Fdoc.pdf?alt=media';

        $this->assertFalse(FirebaseStorageUrlValidator::isValidForInstitute($url, 1));
    }

    public function test_rejects_url_with_port_or_userinfo(): void
    {
        $urlWithPort = 'https://firebasestorage.googleapis.com:8443/v0/b/' . self::TEST_BUCKET . '/o/learning-suite%2Fi1%2Fnotes%2Fdoc.pdf';
        $urlWithUser = 'https://user:pass@firebasestorage.googleapis.com/v0/b/' . self::TEST_BUCKET . '/o/learning-suite%2Fi1%2Fnotes%2Fdoc.pdf';

        $this->assertFalse(FirebaseStorageUrlValidator::isValidForInstitute($urlWithPort, 1));
        $this->assertFalse(FirebaseStorageUrlValidator::isValidForInstitute($urlWithUser, 1));
    }

    public function test_is_valid_file_path_or_url(): void
    {
        // Safe relative paths
        $this->assertTrue(FirebaseStorageUrlValidator::isValidFilePathOrUrl('uploads/notes/grade-4/doc.pdf'));
        $this->assertTrue(FirebaseStorageUrlValidator::isValidFilePathOrUrl('materials/lecture1.pdf'));
        $this->assertTrue(FirebaseStorageUrlValidator::isValidFilePathOrUrl(null));

        // Malicious or invalid paths
        $this->assertFalse(FirebaseStorageUrlValidator::isValidFilePathOrUrl('../victim.txt'));
        $this->assertFalse(FirebaseStorageUrlValidator::isValidFilePathOrUrl('../../.env'));
        $this->assertFalse(FirebaseStorageUrlValidator::isValidFilePathOrUrl('/etc/passwd'));
        $this->assertFalse(FirebaseStorageUrlValidator::isValidFilePathOrUrl('uploads\\notes\\doc.pdf'));

        // Firebase storage URLs
        $validFirebaseUrl = 'https://firebasestorage.googleapis.com/v0/b/' . self::TEST_BUCKET . '/o/learning-suite%2Fi1%2Fnotes%2Fdoc.pdf?alt=media';
        $this->assertTrue(FirebaseStorageUrlValidator::isValidFilePathOrUrl($validFirebaseUrl));

        $foreignFirebaseUrl = 'https://firebasestorage.googleapis.com/v0/b/other-bucket/o/notes.pdf';
        $this->assertFalse(FirebaseStorageUrlValidator::isValidFilePathOrUrl($foreignFirebaseUrl));
    }
}
