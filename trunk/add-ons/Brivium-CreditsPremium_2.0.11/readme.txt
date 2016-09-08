DESCRIPTION
Credits Premium provides functionality for creating and managing virtual points for XenForo with a lot of features (see below). Install Credits Premium and see your users interaction soar by offering them points which could be used to view certain topics, exchange for downloads or even real items.

Credits is modular and this means that it can be easily extended to offer more ways for your users earn and spend points. APIs are also avaiable for other addons to work with Credits Premium.

DIFFERENCES BETWEEN PREMIUM & LITE VERSION ?
- Currencies System: 
+ Supports unlimited concurrent currencies (example: diamond, gold, silver ...)
+ Currencies can be exchanged to anothers & exchange from anothers.
+ Currencies can be withdrawn.
+ Can handling for negative and usergroup viewing permissions.
+ Can reset currencies by criteria.
- Events System: 
+ Can link event with currency.
+ Specify applicable usergroups.
+ Set scenario per currency chosen for user.
+ Supports moderation for any events. (You can set pending for event buy points from Paypal -> can transparently identify and stop fraudulent transactions.)
+ Mix earn / charge currencies on same action.
+ Supports buy points from Paypal event.
+ Supports import/export event.
- Transactions System:
+ All events recorded and queued for performance.
+ Single transaction processed per pageload.
+ Can export transactions to csv file.

MAIN FUNCTIONS
I. Admin Functions:
1. Configure a lot of options example
- Enable/Disable Visitor Tab Display.
- Enable/Disable Message Element Display.
- Enable/Disable Visitor Sidebar Panel Display.
- Enable/Disable Member View Display.
- Enable/Disable Member Card Display.
- Choose to currency will be displayed.
- Can limit the number of members to show on statistics.
- Enable/Disable low priority update SQL queries.
- Can limit the number of transactions that will show per page.
- Can limit the number of days to keep transactions.
- Can set to return credits after delete transaction.
- Can set list of file extenstions will be ignored to be download action.
- Allow Guests to Trigger View Actions.
- Exclude Block BBCode from Size Multipliers.
- Count Words for Size Multipliers.
- Can chooose the member field to complete their profile.
- Reset all User Credits to Zero (with prune existing transactions).
- Reset all User Credits from Zero (without prune existing transactions).
- Give credits from the beginning (without prune existing transactions).

2. Manage Currencies
- Create/Edit/Delete/Enable/Disable a Currency.
- Manage Exchange rate of Currency.
- Set Currency Code.
- Set Currency left/right Symbol.
- Set Decimal Places.
- Set currency can be exchange to other currency.
- Set currency can be exchange from other currency.
- Enable/Disable currency that users can be withdrawed
- Set minimum and maximum amount users can withdraw.
- Set specific usergroups can use currency.

3. Manage Rules/Actions/Events (You can set event for each currency)
- Registration: Assign points to new users.
- Login: Assigns points for each daily login.
- Happy Birthday: Assigns points to user which have birthday.
- Interest: Growing the value of user's credits over time.

- Transfer: Calculate tax and fee when a user transfer credits to other.
- Withdraw: Withdraw credits
- Exchange: Moving your own currency from one to another.
- Buy Credits via Paypal

- Complete/Incomplete Profile: Assigns credits when a user has 100% completed his / her profile.
- Upload/Delete Avatar: Assigns or remove credits when a user upload/delete avatar.
- Update Status: Assigns credits when a user update status.
- Follow/Unfollow: Assigns or remove credits when a user follow/unfollow.
- Get Follower: Assigns or remove credits when a user get/lost follower.
- Profile Post: Assigns credits when a user post comment on other member's profile page.
- Get New Profile Post: Assigns credits when someone comment on user's profile page.
- Like a Profile Post: Assigns or remove credits when a user like/unlike a profile post.
- Receive Profile Post Like: Assigns or remove credits when a user receive/lost profile post like.
- Create New Conversation: Assigns credits when a user create new conversation.

- Create New Thread: Assigns credits when a user create new thread.
- Thread Deleted: Assign credits when a thread of user was deleted.
- Thread get Reply: Assigns credits when someone posting in user's thread.
- Thread Viewed: Assigns credits when someone viewing user's thread. Actions should be limited.
- Read Thread: Assign credits when a user read a thread.
- Create Poll: Assigns credits when a user create a poll.
- Vote Poll: Assigns points to the user after he responded to a poll. Multiplier is the number selected.
- Poll Get a Vote: Assigns credits when a user's poll get a vote.
- Thread get Sticky/Unsticky: Assigns or remove credits when a user's thread becomes sticky/unsticky.

- New Post: Assigns credits when a user create new post.
- Post Deleted: Assigns credits when a post of user was deleted.
- Upload Attachment: Assigns points when a user upload new attachment.
- Download Attachment: Increase or reduced creadits when a user download an attachment file.
- Attachment Downloaded: Assigns points when someone downloading user's attachment. Multiplier is filesize.
- Receive/Lost a Post Like: Assigns or remove credits when a user receive/lost a post like.
- Report Post: Assigns or remove credits when a user report a post. You can use positive or negative values.
- Like/Unlike a Post: Assigns or remove credits when a user like/unlike a post.
- Post get Reported: Remove credits when a post of user get reported.

- Especially for allow third party products to create new action (See developer's documentation)
- Besides this allow to import/export Action settings.

4. Manage Transactions
- Privately list all Transactions.
- View detailed Transaction Informations.
- Delete a/selected Transactions.
- Ordered by latest received and biggest received amount.
- Filtered by Actions, Currency, Username, Start/End date, Date since.
- View list of pending transactions and update to be completed.
- View list of withdraw transactions and update to be completed.
- Can export to file and download list of transactions by filtered.

5. Donations / Transfers
- Transfer credits to members (Use administrator's credits).
- Donate credits to members by usergroup or username.
- Can make anonymous transfer (hide informations with receiver)

6. Cron Job Entries
- Happy Birthday Credits Update.
- Interest Credits Update.
- Transactions Clear History.

7. Credits Statistics
- Total board credits statistics.
- Total board members.
- Average credits earned by day/member.
- Average points spent by day/member .
- Top of richest/poorest member.
- Top of Spent/Earned by day member.

8. Importer Integration
- Supports import credits from vBCredits I, vBCredits II, kBank (vBulletin).
- Supports import credits from [bd] Banking.
- Supports import credits from My Points.
- Supports import credits from Trophy Points.

II. User Functions:
- Trigger actions to get credits.
- View board credits statistics.
- View history of transactions filtered by action.
- Transfer credits to other users.
- Intergrated with alot of third party products.

III. Other Functions:
- Fully Phrased.
- Complete Admin Help.
- Complete Developer's Documentation.