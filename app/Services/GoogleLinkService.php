<?php
namespace App\Services;

use App\Models\GoogleToken;
use RuntimeException;

class GoogleLinkService {
    private const ENDPOINT = 'https://xga-serving-pa.googleapis.com:443';
    private const RPC_PATH = '/google.internal.mothership.xga.v1.standalones.search_share.SearchShareService/BuildDeepLink';

    public function generate(string $longUrl, string $campaign = '', string $source = ''): string {
        $token = GoogleToken::query()->active()->orderBy('id')->value('token');

        if (! is_string($token) || trim($token) === '') {
            throw new RuntimeException('No active Google token found.');
        }

        try {
            $response = $this->sendRequest(
                $this->frame($this->encodeRequest($longUrl, $campaign, $source)),
                trim($token)
            );

            if ($response['grpc_status'] !== '0') {
                throw new RuntimeException(sprintf(
                    'Google gRPC error (%s): %s',
                    $response['grpc_status'],
                    rawurldecode($response['grpc_message'])
                ));
            }

            $responseBytes = $response['body'];
            if (strlen($responseBytes) < 5 || ord($responseBytes[0]) !== 0) {
                throw new RuntimeException('Invalid Google gRPC response frame.');
            }

            $messageLength = unpack('N', substr($responseBytes, 1, 4))[1];
            if (5 + $messageLength > strlen($responseBytes)) {
                throw new RuntimeException('Invalid Google gRPC response frame.');
            }

            $shortUrl = $this->decodeResponse(substr($responseBytes, 5, $messageLength));
            if ($shortUrl === '') {
                throw new RuntimeException('Google response did not contain short_url.');
            }

            return $this->formatGoogleUrl($shortUrl);
        } catch (RuntimeException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            throw new RuntimeException('Unable to generate Google short link.', 0, $exception);
        }
    }

    private function sendRequest(string $frame, string $token): array {
        $headers = [];
        $handle  = curl_init(self::ENDPOINT . self::RPC_PATH);

        if ($handle === false) {
            throw new RuntimeException('Unable to initialize Google HTTP/2 client.');
        }

        curl_setopt_array($handle, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $frame,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_2TLS,
            CURLOPT_TIMEOUT        => (int) config('services.google_link.timeout', 30),
            CURLOPT_HTTPHEADER     => [
                'content-type: application/grpc',
                'te: trailers',
                'authorization: Bearer ' . $token,
            ],
            CURLOPT_HEADERFUNCTION => static function ($handle, string $header) use (&$headers): int {
                $separator = strpos($header, ':');
                if ($separator !== false) {
                    $headers[strtolower(trim(substr($header, 0, $separator)))] = trim(substr($header, $separator + 1));
                }

                return strlen($header);
            },
        ]);

        $body       = curl_exec($handle);
        $error      = curl_error($handle);
        $statusCode = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);

        if ($body === false) {
            throw new RuntimeException('Google HTTP/2 request failed: ' . $error);
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new RuntimeException('Google gRPC endpoint returned HTTP ' . $statusCode . '.');
        }

        return [
            'body'         => $body,
            'grpc_status'  => $headers['grpc-status'] ?? '0',
            'grpc_message' => $headers['grpc-message'] ?? 'Request failed',
        ];
    }

    private function encodeRequest(string $longUrl, string $campaign, string $source): string {
        $link = $this->field(1, $longUrl);
        if ($source !== '') {
            $link .= $this->field(17, $source);
        }
        if ($campaign !== '') {
            $link .= $this->field(19, $campaign);
        }

        return $this->varintField(2, 1) . $this->field(3, $link);
    }

    private function frame(string $message): string {
        return "\x00" . pack('N', strlen($message)) . $message;
    }

    private function decodeResponse(string $message): string {
        $offset = 0;
        while ($offset < strlen($message)) {
            $key         = $this->readVarint($message, $offset);
            $fieldNumber = $key >> 3;
            $wireType    = $key & 7;

            if ($wireType === 2) {
                $length = $this->readVarint($message, $offset);
                $value  = substr($message, $offset, $length);
                $offset += $length;
                if ($fieldNumber === 2) {
                    return $value;
                }
                continue;
            }

            $this->skipValue($message, $offset, $wireType);
        }

        return '';
    }

    private function field(int $number, string $value): string {
        return $this->varint(($number << 3) | 2) . $this->varint(strlen($value)) . $value;
    }

    private function varintField(int $number, int $value): string {
        return $this->varint($number << 3) . $this->varint($value);
    }

    private function varint(int $value): string {
        $encoded = '';
        do {
            $byte = $value & 0x7f;
            $value >>= 7;
            $encoded .= chr($value > 0 ? $byte | 0x80 : $byte);
        } while ($value > 0);

        return $encoded;
    }

    private function readVarint(string $message, int &$offset): int {
        $value = 0;
        $shift = 0;

        while ($offset < strlen($message)) {
            $byte = ord($message[$offset++]);
            $value |= ($byte & 0x7f) << $shift;
            if (($byte & 0x80) === 0) {
                return $value;
            }

            $shift += 7;
            if ($shift > 63) {
                throw new RuntimeException('Invalid protobuf varint.');
            }
        }

        throw new RuntimeException('Unexpected end of protobuf message.');
    }

    private function skipValue(string $message, int &$offset, int $wireType): void {
        if ($wireType === 0) {
            $this->readVarint($message, $offset);
            return;
        }
        if ($wireType === 1) {
            $offset += 8;
            return;
        }
        if ($wireType === 5) {
            $offset += 4;
            return;
        }

        throw new RuntimeException('Unsupported protobuf wire type.');
    }

    private function formatGoogleUrl(string $shortUrl): string {
        $parts = parse_url($shortUrl);

        if (($parts['host'] ?? null) !== 'share.google' || empty($parts['path'])) {
            return $shortUrl;
        }

        return 'https://www.google.com/share.google?q=' . ltrim($parts['path'], '/');
    }
}
