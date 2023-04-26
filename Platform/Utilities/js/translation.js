var $platform_language = [];
var $user_language = [];

function platform_TranslateForUser(phrase, arg1, arg2, arg3, arg4, arg5, arg6, arg7) {
    if ($platform_language[$user_language] && $platform_language[$user_language][phrase]) phrase = $platform_language[$user_language][phrase];
    if (arg1 == undefined)  return phrase;
    phrase = phrase.replace('\%1', arg1);
    if (arg2 == undefined)  return phrase;
    phrase = phrase.replace('\%2', arg2);
    if (arg3 == undefined)  return phrase;
    phrase = phrase.replace('\%3', arg3);
    if (arg4 == undefined)  return phrase;
    phrase = phrase.replace('\%4', arg4);
    if (arg5 == undefined)  return phrase;
    phrase = phrase.replace('\%5', arg5);
    if (arg6 == undefined)  return phrase;
    phrase = phrase.replace('\%6', arg6);
    if (arg7 == undefined)  return phrase;
    return phrase.replace('\%7', arg7);
}

function platform_TranslateForInstance(phrase, arg1, arg2, arg3, arg4, arg5, arg6, arg7) {
    if ($platform_language[$instance_language]&& $platform_language[$instance_language][phrase]) phrase = $platform_language[$instance_language][phrase];
    if (arg1 == undefined)  return phrase;
    phrase = phrase.replace('\%1', arg1);
    if (arg2 == undefined)  return phrase;
    phrase = phrase.replace('\%2', arg2);
    if (arg3 == undefined)  return phrase;
    phrase = phrase.replace('\%3', arg3);
    if (arg4 == undefined)  return phrase;
    phrase = phrase.replace('\%4', arg4);
    if (arg5 == undefined)  return phrase;
    phrase = phrase.replace('\%5', arg5);
    if (arg6 == undefined)  return phrase;
    phrase = phrase.replace('\%6', arg6);
    if (arg7 == undefined)  return phrase;
    return phrase.replace('\%7', arg7);
}
