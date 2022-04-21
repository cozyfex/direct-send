<?php

namespace CozyFex\DirectSend;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class DirectSend
{
    protected string $username = '';
    protected string $key = '';
    protected $client = '';

    public function __construct(string $username, string $key)
    {
        $this->setUsername($username);
        $this->setKey($key);
        $this->client = new Client(['base_uri' => 'https://directsend.co.kr/index.php/api_v2/']);
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    public function sendMail(string $sender, string $sender_name, array $receivers, string $subject, string $body): string
    {
        $receiver = $this->getEmailReceiversString($receivers);
        $headers  = array(
            "cache-control: no-cache",
            "content-type: application/json; charset=utf-8",
        );

        $data = '"subject":"'.$subject.'"';
        $data = $data.', "body":"'.$body.'"';
        $data = $data.', "sender":"'.$sender.'"';
        $data = $data.', "sender_name":"'.$sender_name.'"';
        $data = $data.', "username":"'.$this->username.'"';
        $data = $data.', "receiver":'.$receiver;
        $data = $data.', "key":"'.$this->key.'"';
        $data = '{'.$data.'}';

        $res = json_decode($this->post('https://directsend.co.kr/index.php/api_v2/mail_change_word', $data, $headers));

        return $res->status === '0' ? '' : $res->message;
    }

    public function sendSMS(string $sender, array $receivers, string $title, string $message): string
    {
        $receiver = $this->getSMSReceiversString($receivers);
        $headers  = array(
            "cache-control: no-cache",
            "content-type: application/json; charset=utf-8",
        );

        $data = '"title":"'.$title.'"';
        $data = $data.', "message":"'.$message.'"';
        $data = $data.', "sender":"'.$sender.'"';
        $data = $data.', "username":"'.$this->username.'"';
        $data = $data.', "receiver":'.$receiver;
        $data = $data.', "key":"'.$this->key.'"';
        $data = '{'.$data.'}';

        $res = json_decode($this->post('https://directsend.co.kr/index.php/api_v2/sms_change_word', $data, $headers));

        return $res->status === '0' ? '' : $res->message;
    }

    private function getEmailReceiversString(array $receivers): string
    {
        $result = [];

        foreach ($receivers as $receiver) {
            $result[] = '{"email":"'.$receiver->email.'"}';
        }

        return '['.join(',', $result).']';
    }

    private function getEmailError(string $code): string
    {
        $codeToMessages = [
            '0'   => '정상발송 (성공코드는 다이렉트센드 DB서버에 정상수신됨을 뜻하며 성공/실패의 결과는 발송완료 후 확인 가능합니다.)',
            '100' => 'Subject부터 Key까지의 필수정보가 누락되었거나 ‘’,”” 등의 기호가 누락되었는지 확인해 주시기 바랍니다.',
            '101' => 'User name과 key가 일치하지 않거나, 사전 등록된 서버IP가 없는 경우 입니다. (웹사이트 > 주소록 > API연동관리 참고)',
            '102' => 'Subject, Body를 입력해 주시기 바랍니다.',
            '103' => '보내는 이메일 주소의 형식을 확인해 주시기 바랍니다.',
            '104' => '받는 이메일 주소가 없거나 이메일 형식이 안 맞으니 확인해 주시기 바랍니다.',
            '105' => '.exe 포함되거나, 안되는 문자열이 있는 경우로 해당내용을 제거 후 발송해 주시기 바랍니다. (.exe/.zip/.tar/.msi)',
            '106' => '본문에 유효하지 않은 스크립트가 입력되어 있습니다.',
            '107' => '받는 사람의 이메일이 없습니다. (수신거부에 등록된 주소일 수 있습니다',
            '108' => '예약 시작일이 현재시간 보다 작거나, 예약 종료일이 유효 하지 않습니다.',
            '109' => '결과값을 전달받을 Return_url이 없습니다. url번호를 입력해주시기 바랍니다.',
            '110' => '첨부파일의 URL과 파일명을 지정해주시기 바랍니다.',
            '111' => '첨부파일은 ‘ | (shift+\)’ 로 구분하며 5개까지만 가능합니다.',
            '112' => '첨부파일은 전체 10MB 이하로 발송해야 합니다.',
            '113' => '첨부파일에 문제가 있습니다. 해당 URL로 접근이 가능한지 확인해 주시기 바랍니다.',
            '114' => 'UTF-8 인코딩으로 파라미터가 전달되지 않았습니다.',
            '115' => '사이트에 등록된 템플릿 번호를 다시 확인해 주시기 바랍니다.',
            '200' => '동일 예약시간으로는 200회 이상 API 호출을 할 수 없습니다.',
            '201' => '1분당 300회 이상 API 호출을 할 수 없습니다.',
            '202' => '발송자명은 한영구분없이 35자 이하로 입력해 주시기 바랍니다.',
            '205' => '충전 이후 발송하시기 바랍니다.',
            '999' => '등록된 내용을 다시 한번 확인하고 이상이 없는 경우 고객센터로 문의 바랍니다. ',
        ];

        return $codeToMessages[$code];
    }

    private function getSMSReceiversString(array $receivers): string
    {
        $result = [];

        foreach ($receivers as $receiver) {
            $result[] = '{"mobile":"'.$receiver->mobile.'"}';
        }

        return '['.join(',', $result).']';
    }

    private function getSMSError(string $code): string
    {
        $codeToMessages = [
            '0'   => '정상 발송 정상발송 (성공코드는 다이렉트센드 DB서버에 정상수신됨을 뜻하며 성공/실패의 결과는 발송완료 후 확인 가능합니다.)',
            '100' => 'Post Validation 실패 Message부터 Key까지의 필수정보가 누락되었거나 ‘’,”” 등의 기호가 누락되었는지 확인해 주시기 바랍니다.',
            '101' => 'Sender 유효한 번호가 아님 발송자의 번호가 인증되었는지 웹사이트에서 확인 후 발송해 주시기 바랍니다.',
            '102' => 'Receiver 번호가 유효하지 않음 받은사람의 번호형식을 확인하여 수정 후 발송 해 주세요.',
            '103' => '회원정보가 일치하지 않음 User name과 key가 일치하지 않거나, 사전 등록된 서버IP가 없는 경우 입니다. (웹사이트 > 주소록 > API연동관리 참고)',
            '104' => 'recipient count = 0 받는 사람의 정보가 없습니다.',
            '105' => 'message length = 0 제목은 40byte이하, 내용은 2000byte 이하로 작성해 주세요.',
            '106' => 'message validation 실패 본문에 금지어가 존재합니다. 금지된 내용은 발송이 불가 합니다. 고객센터로 문의 해 주시기 바랍니다.',
            '107' => '이미지 업로드 실패 첨부할 이미지의 URL 및 확장자를 확인하여 주시기 바랍니다.',
            '108' => '이미지 갯수 초과 MMS 첨부 이미지는 3개 이하로만 이용 가능합니다.',
            '109' => 'return_url이 없음 결과값을 전달받을 return_url이 없습니다. url 번호를 입력 하거나 주석처리 후 사용가능합니다.',
            '110' => '이미지 용량 300kb 초과 이미지는 건당 300KB를 초과할 수 없습니다.',
            '111' => '이미지 확장자 오류 이미지는 jpg, jpge 만 업로드 가능합니다.',
            '112' => 'euckr 인코딩 에러 발생 euckr 인코딩 변환에 실패 하엿습니다.',
            '113' => 'utf-8 인코딩 에러 발생 utf-8 인코딩으로 파라미터가 전달되지 않았습니다.',
            '114' => '예약정보가 유효하지 않습니다. 예약시작일이 현재시간보다 작거나, 예약 종료일이 유효하지 않습니다.',
            '200' => '동일 예약시간으로는 200회 이상 API 호출을 할 수 없습니다.',
            '201' => '분당 300회 이상 API 호출을 할 수 없습니다. 1분당 300회 이상 API 호출을 할 수 없습니다. *동일내용으로 발송 시 Receiver 에 수신자를 추가하여 발송 해 주시기 바랍니다.',
            '205' => '잔액 부족 충전 이후 발송하시기 바랍니다.',
            '999' => 'Internal Error 등록된 내용을 다시 한번 확인하고 이상이 없는 경우 고객센터로 문의 바랍니다.',
        ];

        return $codeToMessages[$code];
    }

    public function post(string $url, string $data, $headers = []): bool|string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        return curl_exec($ch);
    }
}