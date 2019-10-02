## Match Tokens

In the template editor, start typing "vol" in the token selector and you will see several tokens with the prefix, "VolMatch:".

#### MatchAppeal

Match based on Opportunities(Appeals). This is currently the only match-token to use the new Opportunity (Appeal) entity.

#### ThisWeek

Opportunities happening in the next week

#### AnyTime

Opportunities that can be done any-time

#### Interest

Match based on interests/area of impact

#### ProfileUrl

Return the URL for the volunteer's profile. Especially used for text-only emails.

#### ProfileLink

Similar to the ProfileURL, but returns a full HTML link

#### SkillsUrl

The URL for a volunteer to update their skills and interests. Especially used for text-only emails.

#### SkillsLink

Similar to the Profile URL, but returns a full HTML link.



## Match API's

Their are now two API methods that will provide suggested opportunities.

The difference is the entity that is used to match with the volunteer.

The original API is `VolunteerNeed.getrecommended` which uses Shifts(Needs) and especially Project fields to make recommendations.

The newer API is `VolunteerAppeal.getrecommended` which uses the new Opportunity(Appeal) entity to make recommendations



## Queue Match Notification Mailing Scheduled Job Parameter Reference

``` shell
created_id=[ID] - optional (default: cron user)
name=[backend UIs] - optional (default: Weekly Volunteer Match)
from_name=[from header] - optional (default: Volunteer Arlington)
from_email=[from email] - optional (defaults to 'volarl@volunteerarlington.org')
send_date=[strtotime()-parseable time to schedule mailing] - optional (default: now)
subject=[email subject] - optional (default: Volunteer opportunities in and around Arlington this week)
msg_template_id=[ID of messageTemplate to use as msg body] - optional (default: 70)
target_group=[ID of group to send mail to] - required
```



## Queuing Mailings

There exists a scheduled job that calls the custom api to create/schedule the mailing.

Someone thought it was a good idea to recommend:

> ~~Queued mailings will not be delivered until Send Scheduled Mailings job runs.~~ **Recommended: leave this job disabled** to keep the parameter doc handy; create new jobs using the same API to accommodate whatever schedules are needed.



Actually including an extension README, like this, may have been a better suggestion for a command-reference.

At minimum, you should supply the `msg_template_id` and the `target_group`.

While `msg_template_id` has a default provided, this seems like a questionable decision and it is surely better to be transparent and specify the msg-template to be used.

The following command could be added as a cron job is you don't want to use the scheduled job:

```shell
cv api MatchMail.create target_group=632 msg_template_id=70 
```

