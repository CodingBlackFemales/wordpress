#!/bin/bash

if [ $# != 1 ]
then
	environment='development'
else
	environment=$1
fi

blog_id=2
url=($(wp @$environment site list --blog_id=${blog_id} --field=url | sed -E 's|https?://||;s|/$||'))
post_id_list=($(wp @$environment post list --post_type=sfwd-quiz --field=ID --url=${url}))

echo "Updating quizzes in $environment environment"
wp @$environment db query "UPDATE wp_${blogid}_learndash_pro_quiz_master SET disabled_answer_mark = 1"

for post_id in ${post_id_list[@]}; do
	echo "Updating quiz ID:${post_id}"
	wp post meta patch update ${post_id} _sfwd-quiz sfwd-quiz_disabledAnswerMark true --url=${url} --format=json
done
