<?php
declare(strict_types=1);

namespace Icube\MisiPintar\Helper;

use GuzzleHttp\Client;
use GuzzleHttp\ClientFactory;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ResponseFactory;
use GuzzleHttp\RequestOptions;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Webapi\Rest\Request;

class GraphQl extends AbstractHelper
{

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        ClientFactory $clientFactory,
        ResponseFactory $responseFactory,
        \Magento\Framework\App\Helper\Context $context
    ) {
        parent::__construct($context);
        $this->clientFactory = $clientFactory;
        $this->responseFactory = $responseFactory;
    }

    /**
     * Get config with scope store
     *
     * @param string $path
     * @return string
     */
    private function getConfigStore(string $path)
    {
        return $this->scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getAuthToken()
    {
        return $this->getConfigStore('misipintar/api/auth_token');
    }

    public function getUrlEndpoint()
    {
        return $this->getConfigStore('misipintar/api/url_endpoint');
    }

    public function getMissionList($customer)
    {
        $query = <<<'GRAPHQL'
query ($wpCode: String!
    $phoneNumber: String!
    $email: String
){
  getEligibleMission(
    wpCode: $wpCode
  	phoneNumber: $phoneNumber
  	email: $email
  ){
      missions{
       	id
        name
        rewardCaption
        type{
            id
            name
            actionButtonCaption
        }
        progress{
          target
          progress
          caption
        }
        status
        details
        target
        startDate
        expireDate
        tnc
      }
    }
}
GRAPHQL;
        $payload = [
            'query' => $query,
            'variables' => [
                'wpCode' => $customer->getCustomAttribute('wp_code') ? $customer->getCustomAttribute('wp_code')->getValue() : '',
                'phoneNumber' => $customer->getCustomAttribute('telephone')->getValue(),
                'email' => $customer->getEmail(),
            ]
        ];
        $params = [
            RequestOptions::HEADERS => [
                'Authorization' => 'Bearer ' . $this->getAuthToken(),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            RequestOptions::BODY => json_encode($payload),
        ];
        $urlEndpoint = $this->getUrlEndpoint();
        $response = $this->sendHttpRequest($urlEndpoint, $params);
        $status = $response->getStatusCode();
        $responseBody = $response->getBody();
        $responseContent = $responseBody->getContents();
        $parsedContent = json_decode($responseContent, true);
        $result = [];
        if ($status === 200 && isset($parsedContent['data']['getEligibleMission'])) {
            $result = $parsedContent['data']['getEligibleMission'];
        }

         //set hardcoded tnc
        if(is_array($result['missions'])){
            foreach ($result['missions'] as $key => $mission) {
                $result['missions'][$key]['tnc'] = $this->getTnc();
            }
        }

        if (isset($parsedContent['errors'])) {
            $this->_logger->critical('Error calling API.', $parsedContent['errors']);
        }

        return $result;
    }

    protected function getTnc()
    {
        return '
        <p>Syarat dan Ketentuan Misi:</p>
        <ol>
        <li>Lakukan pembelanjaan sebanyak-banyaknya sesuai dengan target misi.</li>
        <li>Misi berlaku sampai periode yang tertera di masing-masing misi.</li>
        <li>Berlaku untuk semua produk termasuk Rokok dan Minyak.</li>
        <li>Hanya khusus pembelanjaan di Warung Pintar Distribusi.</li>
        <li>Dapat menyelesaikan Misi Pintar Belanja dengan menggunakan voucher apapun.</li>
        <li>Setelah menyelesaikan misi, hadiah voucher akan dikirimkan paling lambat pada H+2 setelah barang berhasil terkirim dan Juragan telah memenuhi kriteria untuk mendapatkan Voucher Hadiah Misi Pintar. (Tidak Berlaku pada Hari Libur).</li>
        <li>Voucher potongan yang dikirimkan dapat di cek pada Voucher Saya.</li>
        <li>Misi Pintar Belanja hanya dihitung berdasarkan orderan yang berhasil terkirim.</li>
        <li>Syarat dan ketentuan ini dapat berubah sewaktu-waktu tanpa pemberitahuan terlebih dahulu.</li>
        </ol>
        ';
    }

    /**
     * Do API request with provided params
     *
     * @param string $urlEndpoint
     * @param array $params
     * @param string $requestMethod
     *
     * @return Response
     */
    public function sendHttpRequest(
        string $urlEndpoint,
        array $params = [],
        string $requestMethod = Request::HTTP_METHOD_POST
    ): Response {
        /** @var Client $client */
        $client = $this->clientFactory->create();

        try {
            $response = $client->request(
                $requestMethod,
                $urlEndpoint,
                $params
            );
        } catch (GuzzleException $exception) {
            $error = [
                'status' => $exception->getCode(),
                'reason' => $exception->getMessage()
            ];
            /** @var Response $response */
            $response = $this->responseFactory->create(['errors' => $error]);
            $this->_logger->error('Failed to call API.', $error);
        } catch (\Exception $e) {
            $error = [
                'message' => $e->getMessage(),
            ];
            $response = $this->responseFactory->create(['errors' => $error]);
            $this->_logger->error('Error occurred.', $error);
        }

        return $response;
    }
}
