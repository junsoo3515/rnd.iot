<?php

/*******************************************************************************
 * 이 파일은 반드시 UTF-8 형식이어야 한다.
*******************************************************************************/

$str_font				= 'Tahoma,새굴림';
$str_lenguage			= 'ko';
$str_region				= 'en';

$str_title1				= '실시간 악취모니터링 시스템';
$str_title2				= 'Scientec Lab Center co,. LTD';

$str_ok					= '확인';
$str_cancel				= '취소';
$str_make				= '생성';
$str_edit				= '수정';

$str_name				= '이름';
$str_id					= '아이디';
$str_id_error			= '아이디를 입력해야 합니다.';
$str_pw					= '비밀번호';
$str_pw_error			= '비밀번호를 입력해야 합니다.';

$str_save				= '저장';
$str_save_ing			= '저장중..';
$str_save_ok			= '저장되었습니다.';
$str_save_error			= '저장할 수 없습니다.';

$str_delete				= '삭제';
$str_delete_confirm		= '삭제하시겠습니까? 복구할 수 없습니다.';
$str_delete_ing			= '삭제중..';
$str_delete_ok			= '삭제되었습니다.';
$str_delete_fail		= '삭제할 수 없습니다.';

$str_view_all			= '전체보기';
$str_select_site		= '지점 선택';
$str_select_item		= '항목 선택';
$str_comm_error			= '통신오류';
$str_nodata				= 'No data';

$str_update				= 'Update';
$str_olddate			= 'OldData';
$str_time				= '시간';
$str_year				= '년';
$str_month				= '월';
$str_day				= '일';
$str_average			= '평균';
$str_sum				= '합계';

$str_move_left			= '◀';
$str_movr_right			= '▶';
$str_move_up			= '▲';
$str_move_down			= '▼';
$str_sort_ascent		= '△';
$str_sort_descent		= '▽';
$str_slash				= '／';

$str_weather_noresponse = 'Error: The weather server is not responding.';
$str_weather_unknown	= 'Error: Unknown weather code.';
$str_weather_nodata		= 'Error: No weather data.';

$arr_weather_pty = array('없음','비','비/눈','눈');
$arr_weather_sky = array('-','맑음','구름조금','구름많음','흐림');
$arr_weather_vec = array('북풍','북동풍','동풍','남동풍','남풍','남서풍','서풍','북서풍','북풍');
$arr_weather_etc = array('기온','℃','습도');

$arr_win_dir = array('북','북북서','북서','서북서','서','서남서','남서','남남서','남','남남동','남동','동남동','동','동북동','북동','북북동');
$str_win_dir_comment = '* 세로축 범례 : 0:북풍, 90:서풍, 180:남풍, 270:동풍';
$arr_pozip = array('포집준비','포집완료');
$arr_alm = array('정상', '경보', '위험', '심각');
$str_alm_comment = '* 세로축 범례 : 0:정상, 1:경보, 2:위험, 3:심각';

$str_login				= '로그인';
$str_login_error		= '<font color=#FF4444>오류 : 아이디</font><font color=#666666>와 </font><font color=#FF4444>비밀번호</font><font color=#666666>를 확인하세요.</font>';
$str_login_msg			= '<font color=#4444FF>아이디</font><font color=#666666>와 </font><font color=#4444FF>비밀번호</font><font color=#666666>를 입력하세요.</font>';
$str_login_block		= 'admin 원격 접속 차단';
$str_login_locked		= '계정은 잠겨 있습니다.';
$str_login_locktime		= '남은 시간';
$str_login_unlock		= '계정 잠김이 해제되었습니다.';
$str_logout				= '로그아웃';

$str_realtime			= '실시간 악취정보';

$str_report				= '보고서';
$str_report_year		= '연간';
$str_report_month		= '월간';
$str_report_day			= '일일';
$str_report_term		= '기간';
$str_report_location	= '장소';
$str_report_date		= '일자';
$str_report_manager		= '담당자';
$str_report_1			= '모니터링 자료';
$str_report_2			= '악취 변화';
$str_report_3			= 'Abnormal value';
$str_report_item		= '항목';
$str_report_min			= '최저값';
$str_report_max			= '최고값';
$str_report_measures	= '측정값';
$str_report_reference	= '기준값';

$str_data				= '자료조회';
$str_data_all			= '전체';
$str_data_search		= '검색';
$str_data_search_terms	= '검색기간';
$str_data_raw			= '측정자료';
$str_data_graph			= '그래프';
$str_data_ou			= '악취 희석배수(OU)에 따른 자료분포';
$str_data_sort			= '정렬';
$str_data_site			= '지점';
$str_data_overday		= '검색 시작날자가 오늘 이전 이어야 합니다.';
$str_data_minday		= '검색 시작날자는 끝날자보다 이전 이어야 합니다.';
$str_data_maxday		= '검색 기간이 너무 깁니다. 최대';
$str_data_nosite		= '선택된 [지점]이 없습니다.';
$str_data_noitem		= '선택된 [항목]이 없습니다.';
$str_data_onesite		= '[지점] 한곳을 선택해야 합니다.';
$str_calendar_day		= "['일','월','화','수','목','금','토']";
$str_calendar_month		= "['1','2','3','4','5','6','7','8','9','10','11','12']";

$str_info				= '정보';

$str_chpw				= '비밀번호 변경';
$str_chpw_err1			= '이전 암호를 입력해야 합니다.';
$str_chpw_err2			= '변경할 암호를 입력해야 합니다.';
$str_chpw_err3			= '변경할 암호가 일치하지 않습니다.';
$str_chpw_err4			= '<font color=#FF4444>이전 암호</font><font color=#666666>가 일치하지 않습니다.</font>';
$str_chpw_ok			= '암호가 변경되었습니다.';
$str_chpw_pw1			= '이전 암호';
$str_chpw_pw2			= '변경할 암호';
$str_chpw_pw3			= '변경할 암호 확인';

$str_ad					= '제품소개';
$str_ad_msg				= '당사의 악취측정장치 제품은 악취의 강도, 악취 물질의 종류 및 농도의 정확한 측정 분석을 위해 당사가 세계 최초로 개발한 가스 센서 출력신호 해석기술을 기반으로 만들어낸 획기적인 제품입니다.';
$str_ad_1				= '휴대용 악취 측정기(기본형)';
$str_ad_2				= '전자코 기술의 악취분석 시스템(고급형)';
$str_ad_3				= '실시간 악취 관리 시스템';

$str_maptype			= '지도종류';
$str_browsertype		= '기기형태';

$str_board				= '공지사항';
$str_board_err1			= '제목을 입력해 주세요.';
$str_board_err2			= '작성자를 입력해 주세요.';
$str_board_err3			= '내용을 입력해 주세요.';
$str_board_err4			= '입력값이 정확하지 않습니다.';
$str_board_err6			= '게시물이 존재하지 않습니다.';
$str_board_title		= '제 목';
$str_board_name			= '작성자';
$str_board_date			= '등록일';
$str_board_count		= '조회수';
$str_board_email		= '이메일';
$str_board_content		= '내용';
$str_board_file			= '첨부파일';
$str_board_list			= '목록';
$str_board_number		= '번호';
$str_board_write		= '글쓰기';

$str_admin				= '관리자';
$str_admin_msg			= '좌측 메뉴에서 작업을 선택하세요.';

$str_db					= '데이터베이스';
$str_db_msg				= '사용금지. 데이터베이스 메뉴는 유지보수용입니다.';
$str_db_get_msg			= '다른 Database 자료를 가져옵니다. <b>pAdmin.php [cdget] 코드확인 필수.</b>';
$str_db_del_msg			= '주의! 모든 자료가 삭제됩니다.';

$str_etc				= '설정';
$str_etc_common			= '공통';
$str_etc_title			= '사이트 이름';
$str_etc_isguest		= '익명 사용자 (0:차단, 1:허용)';
$str_etc_isadmin		= '원격 관리자 (0:차단, 1:허용)';
$str_etc_rtupdate		= '실시간화면 갱신 주기 (초)';
$str_etc_rtold			= '실시간화면 OldData 기준 (시간)';
$str_etc_isweather		= '기상 정보 (0:미사용, 1:사용)';
$str_etc_isgps			= 'GPS 위치 (0:미사용, 1:사용)';
$str_etc_pagesize		= '페이지당 검색결과 수 (줄)';
$str_etc_maxdates		= '검색가능한 최대 기간 (일)';
$str_etc_boardline		= '페이지당 게시글 갯수 (줄)';

$str_etc_range_err1		= ' : 허용 범위(';
$str_etc_range_err2		= ') 벗어남';

$str_rsa				= '암호화';
$str_rsa_no				= '없음';
$str_rsa_msg			= '안전한 비밀번호 전송을 위해서 비대칭 암호화 기술인 RSA 알고리즘을 사용합니다.<br>bit 수가 커질수록 생성에 많은 시간이 소요됩니다. 2048bit를 권장합니다.';
$str_rsa_ing			= '새로운 RSA Key 생성중...';
$str_rsa_new			= '새로운 RSA Key 가 생성되었습니다.';
$str_rsa_err			= 'RSA Key 생성 오류.';
$str_rsa_del			= 'RSA Key 를 삭제했습니다.';
$str_rsa_warning		= '<font color=#FF0000>OPEN SSL 이 설정되어 있지 않습니다.</font><br>OPEN SSL 이 없으면 암호처리 속도가 느려집니다. 속도 증가를 위해서<br>APM_Setup/php.ini 의 extension=php_openssl.dll 주석을 제거하고 서버를 다시 시작하세요.';

$str_input				= '자료 입력';
$str_input_msg			= '테스트를 위해서 서버에 임의로 자료를 입력합니다.';
$str_input_single		= '단일입력';
$str_input_single_msg	= '현재 시각의 자료를 생성합니다.<br>입력하지 않은 항목은 해당 센서가 없는 것으로 설정됩니다.<br>단일입력: DB 에 직접 자료를 저장합니다.<br>aodc.php: 장비에서 자료를 보내는 것 처럼 aodc.php 를 호출합니다.';
$str_input_multi		= '다중입력';
$str_input_multi_msg	= 'Date2 ~ Date 기간의 자료를 DB 에 직접 저장합니다.<br>5분 단위로 저장되며 최대 1개월까지 가능합니다.<br>기간이 커지면 시간이 많이 소요됩니다.';
$str_input_start		= '시작날자';

$str_item				= '측정 항목 관리';
$str_item_id			= '아이디';
$str_item_name			= '이름';
$str_item_unit			= '단위';
$str_item_dec			= '종류';
$str_item_lo			= '하한값';
$str_item_hi			= '상한값';
$str_item_bottom		= '최소값';
$str_item_top			= '최대값';
$str_item_warning		= '주의';
$str_item_danger		= '경고';
$str_item_comment		= '주석';
$str_item_using			= '사용';
$str_item_important		= '중요값';
$str_item_realtime		= '실시간';
$str_item_used			= '사용';
$str_item_notused		= '미사용';
$str_item_type			= array('0','1','2','3','알람','풍향','포집');

$str_item_msg			=
	'<table border=0 cellpadding=0 cellspacing=0>'.
	'<tr><td>종류</td><td>&nbsp;:&nbsp;</td><td colspan=2><b>센서 종류</b></td></tr>'.
	'<tr><td colspan=2></td><td>0</td><td>: 센서 정밀도가 정수</td></tr>'.
	'<tr><td colspan=2></td><td>1</td><td>: 센서 정밀도가 소수점 이하 1자리</td></tr>'.
	'<tr><td colspan=2></td><td>2</td><td>: 센서 정밀도가 소수점 이하 2자리</td></tr>'.
	'<tr><td colspan=2></td><td>3</td><td>: 센서 정밀도가 소수점 이하 3자리</td></tr>'.
	'<tr><td colspan=2></td><td>알람</td><td>: 센서 알람</td></tr>'.
	'<tr><td colspan=2></td><td>풍향</td><td>: 풍향 센서</td></tr>'.
	'<tr><td colspan=2></td><td>포집</td><td>: 포집 센서</td></tr>'.
	'<tr height=5><td colspan=4></td></tr>'.
	'<tr><td>하한값</td><td>&nbsp;:&nbsp;</td><td colspan=2>측정값이 하한값 미만이면 실시간 화면에 하한경고를 표시하고 막대 그래프를 표시.</td></tr>'.
	'<tr><td>상한값</td><td>&nbsp;:&nbsp;</td><td colspan=2>측정값이 상한값 초과면 실시간 화면에 상한경고를 표시하고 막대 그래프를 표시.</td></tr>'.
	'<tr><td colspan=2></td><td colspan=2>* [ 상한값 < 하한값 ] 이면 상한값은 주의, 하한값은 경고값으로 사용됨.</td></tr>'.
	'<tr height=5><td colspan=4></td></tr>'.
	'<tr><td>최소값</td><td>&nbsp;:&nbsp;</td><td colspan=2>자료조회 화면의 그래프 하한값.</td></tr>'.
	'<tr><td>최대값</td><td>&nbsp;:&nbsp;</td><td colspan=2>자료조회 화면의 그래프 상한값.</td></tr>'.
	'<tr height=5><td colspan=4></td></tr>'.
	'<tr><td>주석</td><td>&nbsp;:&nbsp;</td><td colspan=2>실시간 화면 하단의 항목 이름에 마우스를 올리면 보여짐.</td></tr>'.
	'<tr height=5><td colspan=4></td></tr>'.
	'<tr><td>사용</td><td>&nbsp;:&nbsp;</td><td colspan=2><b>센서 표출 형태</b></td></tr>'.
	'<tr><td colspan=2></td><td>중요값</td><td>: 실시간 화면 전체보기에 표시</td></tr>'.
	'<tr><td colspan=2></td><td>실시간</td><td>: 실시간 화면 하단 창에 값 표시</td></tr>'.
	'<tr><td colspan=2></td><td>사용</td><td>: 사용자가 선택할 때만 표시되는 항목</td></tr>'.
	'<tr><td colspan=2></td><td>미사용</td><td>: 사용하지 않는 항목 (admin 에서는 [사용]과 동일)</td></tr>'.
	'</table>';
$str_item_no_name		= '경고! 이름이 없는 항목이 있습니다.';
$str_item_dup_name		= '경고! 중복된 이름이 있습니다.';

$str_user				= '사용자 관리';
$str_user_ecnt			= '실패횟수';
$str_user_lockmsg		= '로그인에 3회 이상 실패하면 일정 시간 동안 계정을 사용할 수 없습니다.<br>저장 버튼을 누르면 해당 계정의 잠김이 해제됩니다.';
$str_user_add			= '신규등록';

$str_site				= '측정지점 관리';
$str_site_no			= '지점번호';
$str_site_nomsg			= '4자리 지점번호를 입력하세요.';
$str_site_noerr			= '지점번호 중복';
$str_site_name			= '지점명';
$str_site_namemsg		= '지점명을 입력하세요.';
$str_site_addr			= '주소';
$str_site_remark		= '비고';
$str_site_edit			= '지점 수정';
$str_site_add			= '지점 추가';
$str_site_add_ok		= '추가되었습니다.';
$str_site_add_fail		= '추가할 수 없습니다. 동일한 아이디가 있습니다.';
$str_normal_map			= '일반지도';
$str_satellite_map		= '위성지도';

?>