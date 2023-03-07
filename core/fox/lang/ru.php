<?php namespace fox\lang;

class ru {
    const eMailInviteMessageTitle="Приглашение пользователя на \${svcName}";
    const eMailInviteMessage="<div id='preheader' style='display:none;'>Приглашение пользователя на \${svcName}</div>
<div  style='font-family: sans-serif; color: #02394E'>
Добрый день!<br/>
Вас пригласили для регистрации на сайте <a style='color:  #ff6f0f; text-decoration: underline;' href='\${sitePrefix}'>\${svcName}</a><br/>
<br/>
    
Для регистрации регистрации укажите код <span style='color: #ff6f0f'>\${regCodePrint}</span> в форме регистрации по адресу <a style='color:  #ff6f0f; text-decoration: underline;' href='
\${sitePrefix}/auth/register'>\${sitePrefix}/auth/register</a><br/>
или перейдите по <a style='color:  #ff6f0f; text-decoration: underline;' href='
\${sitePrefix}/auth/register?code=\${regCodePrint}'>ссылке</a><br/>
<br/>
Если регистрация не требуется либо письмо было отправлено по ошибке - просто проигнориуйте его.
<br/>
    
Отвечать на это письмо не нужно, так как оно отправлено автоматически.
<br/>
<br/>
С уважением,<br/>
Команда <span style='color: #ff6f0f'>\${svcName}</span>
    
</div>
";
    const eMailConfirmMessageTitle="Подтверждение адреса почты для \${svcName}";
    const eMailConfirmMessage="<div id='preheader' style='display:none;'>Код подтверждения \${confCodePrint}<br/></div>
<div  style='font-family: sans-serif; color: #02394E'>
Добрый день!<br/>
        
Для подтверждения адреса электронной почты укажите код <span style='color: #ff6f0f'>\${confCodePrint}</span> в форме подтверждения по адресу <a style='color:  #ff6f0f; text-decoration: underline;' href='
\${sitePrefix}/core/userEmailConfirm'>\${sitePrefix}/core/userEmailConfirm</a><br/>
или перейдите по <a style='color:  #ff6f0f; text-decoration: underline;' href='
\${sitePrefix}/core/userEmailConfirm?code=\${confCodePrint}'>ссылке</a><br/>
<br/>
Если Вы не запрашивали подтверждения адреса либо письмо отправлено по ошибке - просто проигнориуйте его.
<br/>
        
Отвечать на это письмо не нужно, так как оно отправлено автоматически.
<br/>
<br/>
С уважением,<br/>
Команда <span style='color: #ff6f0f'>\${svcName}</span>
        
</div>
";

    const accessRecoverMessageTitle="Восстановление доступа \${svcName}";
    const accessRecoverMessage="<div id='preheader' style='display:none;'>Код подтверждения \${confCodePrint}<br/></div>
<div  style='font-family: sans-serif; color: #02394E'>
Добрый день!<br/>
    
Для восстановления доступа укажите код <span style='color: #ff6f0f'>\${confCodePrint}</span> в форме восстановления по адресу <a style='color:  #ff6f0f; text-decoration: underline;' href='
\${sitePrefix}/auth/recover'>\${sitePrefix}/auth/recover</a><br/>
или перейдите по <a style='color:  #ff6f0f; text-decoration: underline;' href='
\${sitePrefix}/auth/recover?code=\${confCodePrint}&address=\${eMailEncoded}'>ссылке</a><br/>
<br/>
Если Вы не запрашивали восстановление доступа - просто проигнориуйте его либо сообщите нам.
<br/>
    
Отвечать на это письмо не нужно, так как оно отправлено автоматически.
<br/>
<br/>
С уважением,<br/>
Команда <span style='color: #ff6f0f'>\${svcName}</span>
    
</div>
";
    const timeIntervalsShort=[
        "days"=>"Дн",
        "weeks"=>"Нед",
        "months"=>"Мес",
        "years"=>"Лет",
    ];

    const timeIntervals=[
        "days"=>"Дней",
        "weeks"=>"Недель",
        "months"=>"Месяцев",
        "years"=>"Лет",
    ];

    const calendarMonths=[
        0=>"Мартобрь",
        1=>"Январь",
        2=>"Февраль",
        3=>"Март",
        4=>"Апрель",
        5=>"Май",
        6=>"Июнь",
        7=>"Июль",
        8=>"Август",
        9=>"Сентябрь",
        10=>"Октябрь",
        11=>"Ноябрь",
        12=>"Декабрь",
    ];

    const calendarMonthsRod=[
        0=>"Мартобря",
        1=>"Января",
        2=>"Февраля",
        3=>"Марта",
        4=>"Апреля",
        5=>"Мая",
        6=>"Июня",
        7=>"Июля",
        8=>"Августа",
        9=>"Сентября",
        10=>"Октября",
        11=>"Ноября",
        12=>"Декабря",
    ];

    const calendarMonthsShort=[
        0=>"Мтб",
        1=>"Янв",
        2=>"Фев",
        3=>"Мар",
        4=>"Апр",
        5=>"Май",
        6=>"Июн",
        7=>"Июл",
        8=>"Авг",
        9=>"Сен",
        10=>"Окт",
        11=>"Ноя",
        12=>"Дек",
    ];


}


?>