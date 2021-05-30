
# Installation

cp .env.example .env

Add four DBs for Common, Transport, Order, Service. Then add it to env.

Add APP_URL value in .env

composer install

## Key Generate
php artisan key:generate

php artisan jwt:secret

php artisan migrate --path=database/migrations/* 

php artisan db:seed

php artisan company:seed

php artisan webpush:vapid


Run payroll_trigger.sql and store_payroll_trigger.sql in Payrolls Table

## Change URL

*In companies table change base_url, and socket_url. In admin_services table change base_url*

*In site_sttings table, in settings_data column change CMS pages api url with website link. e.g. https://api.demo.com to https://demo.com*

## Redis Installation

sudo apt update

sudo apt install redis-server

sudo nano /etc/redis/redis.conf

Change **supervised no** to **supervised systemd**

Change **maxmemory <bytes>** to **maxmemory 1536mb**

Change **maxmemory-policy noeviction** to **maxmemory-policy allkeys-random**

*Save and exit the editor*

sudo systemctl restart redis.service

## Directories and Permissions

*Enter your project path*

sudo mkdir -p storage/app/public

ln -s [PROJECT_PATH]/storage/app/public/ public/storage


sudo mkdir -p public/uploads

sudo chmod -R 777 public/uploads/

sudo chmod -R 777 storage/ bootstrap/ config/

sudo chown -R www-data storage/ public/

sudo chgrp -R www-data storage/ public/

sudo chmod -R ug+rwx storage/

## Install Node

sudo apt install curl

curl -sL https://deb.nodesource.com/setup_12.x | sudo -E bash -

sudo apt-get install nodejs

npm install

sudo npm install forever -g

## Run Node

forever start nodejs/server.js

## Kill Node port (Only to stop node)

killall -9 node

node nodejs/server.js

## Run Application

*For web server*
php artisan serve

*For API server*
php artisan serve --port={PORT_NO}

## Generate Swagger API Documentation

php artisan swagger:generate


## Remote Server Configuration

### In Site Config 

```
ProxyRequests off
ProxyPreserveHost On
ProxyVia Full
<Proxy *>
   Require all granted
</Proxy>

<Location /socket.io>
   ProxyPass http://localhost:{PORT_NO}/
   ProxyPassReverse http://localhost:{PORT_NO}/
</Location>
RewriteEngine On

# socket.io 1.0+ starts all connections with an HTTP polling request
RewriteCond %{QUERY_STRING} transport=polling       [NC]
RewriteRule /(.*)           http://localhost:{PORT_NO}/$1 [P]
# When socket.io wants to initiate a WebSocket connection, it sends an
# "upgrade: websocket" request that should be transferred to ws://
RewriteCond %{HTTP:Upgrade} websocket               [NC]
RewriteRule /(.*)           ws://localhost:{PORT_NO}/$1  [P]
```

### Run following commands in Ubuntu server

sudo a2enmod proxy

sudo a2enmod proxy_balancer

sudo a2enmod proxy_http

sudo a2enmod proxy_wstunnel


*If your port number is blocked by firewall, then use the following command*
sudo ufw allow {PORT_NO}/tcp

------

# Calculations

## TRANSPORT

| Pricing Logic                                  | Formula                   |
| ---------------------------------------------- |--------------------------:|
| Per Minute Pricing(MIN)                        | BP+(TM*PM)                |
| Per Hour Pricing(HOUR)                         | BP+(TH*PH)                |
| Distance Pricing(DISTANCE)                     | BP+((TKM-BD)*PKM)         |
| Distance and Per Minute Pricing(DISTANCEMIN)   | BP+((TKM-BD)*PKM)+(TM*PM) |
| Distance and Per Hour Pricing(DISTANCEHOUR)    | BP+((TKM-BD)*PKM)+(TH*PH) |

```
BP  - Base Price
BD  - Base Distance
TM  - Total Minutes
TH  - Total Hours
TKM - Total Kilo Meter
PM  - Per Minute
PH  - Per Hour
PKM - Per Kilo Meter

Estimation = Pricing Logic + Tax
```


### Example with Distance Pricing
BP + (TKms-BD\*PKms)



BP=100$

Tkms=1.67kms

BD=1 km

Pkm=20$

Tax=10%

Commission=5%

fleet_comission=2%

waiting=1$

waiting_comission=2%

peak_price=5$

peak_comission=2%

Discount = 10% (Maximum 10$)

Waiting charge per minute = 1

Waiting Charge = 2




Price = BP + (TKms-BD\*PKms) = 100+13.4 = 113.4


Tax=(10/100)\*Price=11.34

Commission=(5/100)\*(Price+Tax)=(113.4+11.34)\*5%=124.74\*5% = 6.237 = 6.24


Price + Commission + Tax = 130.41


Fixed = BP + Commision + peakamount = 100 + 6.24 + 5 = 111.24

Distance = (TKms-BD\*PKms) = 13.4

Commission = Commission% \* Price = 6.24

Peak Comission = peak_price \* peak_comission% = 5 \* 2% = 0.10

Waiting Comission = Waiting Charge \* waiting_comission% = 2 \* 2%  = 0.04

Discount = ((Price + Tax) \* (Discount/100)) || Discount = 10

Tax = Price \* ( tax_percentage/100 ) = 11.34

Total = Price + Tax + Commision + peakamount + total_waiting_amount + toll_price = 113.4 + 11.34 + 6.24 + 5 + 2 = 137.98




***roundof** - only if paid through cash*


Payable Amount = Price + Tax - Discount + Commision + peakamount + total_waiting_amount + toll_price


= 113.4 + 11.34 - 10 + 6.24 + 5 + 2 = 127.98 = 128 (Round off)


Payable = Payable Amount


Provider Pay = ((Total+Discount) - Commision)-Tax + (peakamount+total_waiting_amount) + toll_price


= (147.98 - 6.24) - 11.34 = 130.4






### Wallet

| Account                      | Debit   | Credit | Note                 |
| ---------------------------- |:-------:|:------:|---------------------:|
| Admin                        |         | $      | recharge             |
| User                         |         | $      | recharge             |



#### Cash Transaction
*Amount will be given to provider and admin commission will be added to provider wallet as negative balance.*

**Admin Comission**


| Account                      | Debit   | Credit | Note               |
| ---------------------------- |:-------:|:------:|-------------------:|
| Admin                        |         | 6.24   | admin commission   |
| Provider                     | 6.24    |        | admin commission   |


**Fleet Commission**

| Account                      | Debit   | Credit | Note               |
| ---------------------------- |:-------:|:------:|-------------------:|
| Admin                        | $       |        | fleet_debit        |
| Provider                     |         | $      | fleet_add          |
| Admin                        |         | $      | fleet_recharge     |

**Discount Transaction**

| Account                      | Debit   | Credit | Note                               |
| ---------------------------- |:-------:|:------:|-----------------------------------:|
| Admin                        | 10.00   |        | discount applied                   |
| Provider                     |         | 10.00  | discount amount refund             |
| Admin                        | 10.00   |        | provider discount amount recharge  |

**Tax Transaction**

| Account                      | Debit   | Credit | Note               |
| ---------------------------- |:-------:|:------:|-------------------:|
| Provider                     | 11.34   |        | tax_debit          |
| Admin                        |         | 11.34  | tax_credit         |

**Peak Amount**

| Account                      | Debit   | Credit | Note               |
| ---------------------------- |:-------:|:------:|-------------------:|
| Provider                     | 0.10    |        | peak_commission    |
| Admin                        |         | 0.10   | peak_commission    |

**Waiting Amount**

| Account                      | Debit   | Credit | Note               |
| ---------------------------- |:-------:|:------:|-------------------:|
| Provider                     | 0.04    |        | waiting_commission |
| Admin                        |         | 0.04   | waiting_commission |

**Provider Pay**

| Account                      | Debit   | Credit | Note               |
| ---------------------------- |:-------:|:------:|-------------------:|
| Provider                     | 130.4   |        | transport          |
| Admin                        | 130.4   |        | transport          |






#### Wallet Transaction
*Amount will be deducted from user and added to admin. Then provider pay will be added to provider wallet.*

**Wallet Transaction**


| Account                      | Debit   | Credit | Note               |
| ---------------------------- |:-------:|:------:|-------------------:|
| Admin                        | 135.97  |        | transport deduction|
| User                         | 135.97  |        | transport deduction|


**Admin Comission**

| Account                      | Debit   | Credit | Note               |
| ---------------------------- |:-------:|:------:|-------------------:|
| Admin                        |         | 6.24   | admin commission   |
| Provider                     | 6.24    |        | admin commission   |


**Tax Comission**

| Account                      | Debit   | Credit | Note               |
| ---------------------------- |:-------:|:------:|-------------------:|
| Provider                     | 11.34   |        | tax_debit          |
| Admin                        |         | 11.34  | tax_credit         |


**Peak Amount**

| Account                      | Debit   | Credit | Note               |
| ---------------------------- |:-------:|:------:|-------------------:|
| Provider                     | 0.10    |        | peak_commission    |
| Admin                        |         | 0.10   | peak_commission    |


**Provider Pay**

| Account                      | Debit   | Credit | Note               |
| ---------------------------- |:-------:|:------:|-------------------:|
| Provider                     | 118.4   |        | transport          |
| Admin                        | 118.4   |        | transport          |







#### Card Transaction
*Amount will be deducted from user and added to admin. Then provider pay will be added to provider wallet. Similar to wallet transaction.*



#### Wallet + CASH
*Amount will be deducted from user wallet and added to admin. Then provider pay subtracted from remaining cash will be added to provider wallet. Similar to wallet transaction. 100 deducted from wallet and 36 received as cash. 
135.98 - 35.88 = 100.1
100.1 - (6.24 + 11.34 + 0.10) =  100.1 - 17.68 = 82.42
82.42 will be added to provider wallet*


**Wallet Transaction**


| Account                      | Debit   | Credit | Note               |
| ---------------------------- |:-------:|:------:|-------------------:|
| Admin                        | 100.00  |        | transport deduction|
| User                         | 100.00  |        | transport deduction|


**Admin Comission**

| Account                      | Debit   | Credit | Note               |
| ---------------------------- |:-------:|:------:|-------------------:|
| Admin                        |         | 6.24   | admin commission   |
| Provider                     | 6.24    |        | admin commission   |


**Tax Comission**

| Account                      | Debit   | Credit | Note               |
| ---------------------------- |:-------:|:------:|-------------------:|
| Provider                     | 11.34   |        | tax_debit          |
| Admin                        |         | 11.34  | tax_credit         |


**Peak Amount**

| Account                      | Debit   | Credit | Note               |
| ---------------------------- |:-------:|:------:|-------------------:|
| Provider                     | 0.10    |        | peak_commission    |
| Admin                        |         | 0.10   | peak_commission    |


**Provider Pay (118.4 - 36)**

| Account                      | Debit   | Credit | Note               |
| ---------------------------- |:-------:|:------:|-------------------:|
| Provider                     | 82.42   |        | transport          |
| Admin                        | 82.42   |        | transport          |




#### Wallet + CARD
*Amount will be deducted from user and added to admin. Then provider pay will be added to provider wallet. Similar to wallet transaction.*


**Wallet Transaction**


| Account                      | Debit   | Credit | Note               |
| ---------------------------- |:-------:|:------:|-------------------:|
| Admin                        | 100.00  |        | transport deduction|
| User                         | 100.00  |        | transport deduction|


**Admin Comission**

| Account                      | Debit   | Credit | Note               |
| ---------------------------- |:-------:|:------:|-------------------:|
| Admin                        |         | 6.24   | admin commission   |
| Provider                     | 6.24    |        | admin commission   |


**Tax Comission**

| Account                      | Debit   | Credit | Note               |
| ---------------------------- |:-------:|:------:|-------------------:|
| Provider                     | 11.34   |        | tax_debit          |
| Admin                        |         | 11.34  | tax_credit         |


**Peak Amount**

| Account                      | Debit   | Credit | Note               |
| ---------------------------- |:-------:|:------:|-------------------:|
| Provider                     | 0.10    |        | peak_commission    |
| Admin                        |         | 0.10   | peak_commission    |


**Provider Pay**

| Account                      | Debit   | Credit | Note               |
| ---------------------------- |:-------:|:------:|-------------------:|
| Provider                     |         | 135.98  | transport         |
| Admin                        |         | 118.40  | transport         |



#### Payroll


| Account                      | Debit   | Credit | Note                            |
| ---------------------------- |:-------:|:------:|--------------------------------:|
| Provider                     |$        |        | Amount deducted by admin        |
| Admin (If amount > 0)        |         | $      | Amount transferred to provider  |



## ORDER


GST/TAX=10%

Commission=5%

Shop Offer=5%

Delivery Charge=50$

Packing Charges=10$

Item Discount=5$



Subtotal =	$300.00

Shop Offer = 5% \* $300.00 = 15

Shop GST = 10% \* $300.00 = 10% \* $300.00 = 30

Shop Package Charge = $10

Delivery Charge = $50


Total = (Subtotal + Shop GST + Shop Package Charge + Delivery Charge) - Shop Offer  = (300 + 30 + 10 + 50) - 15 = $375


Admin Commission = Total * Commission% = 375\*5% = 18.75


Shop Payment = Total - Commission - Delivery charge = 375-18.75-50 = 306.25


If promocode added,admin will bare the amount(Not showing in any record)
Shop Discount(Shop will bare the amount,no record for the amount is shown)


**Provider Transaction**


| Account                      | Debit   | Credit | Note                           |
| ---------------------------- |:-------:|:------:|-------------------------------:|
| Provider                     |         | 50.00  | Order delivery amount sent     |
| Admin                        | 50.00   |        | Order delivery amount recharge |


**Shop Comission**

| Account                      | Debit   | Credit | Note                  |
| ---------------------------- |:-------:|:------:|----------------------:|
| Shop                         |         | 306.25 | Order amount recevied |
| Admin                        | 306.25  |        | Order amount sent     |
| Admin                        |         | 306.25 | Order amount recharge |


**Admin Comission**

| Account                      | Debit   | Credit | Note                   |
| ---------------------------- |:-------:|:------:|-----------------------:|
| Admin                        |         | 18.75  | Shop Commission added  |





## SERVICE

| Pricing Logic | Formula                   |
| --------------|--------------------------:|
| FIXED         | BP * Qty                  |
| HOURLY        | BP +(PM * TM)             |
| DISTANCETIME  | BP +(PM * TM) + (BD * TD) |


```
BP  - Base Price
Qty - Quantity
PM  - Per Minute
TM  - Total Minutes
BD  - Base Distance
TD  - Total Distance
```


### FIXED Example


BP * Qty

Base Fare=50

Commission=10%

Minute=Base Fare=50

Tax=10%

Base Price = Base Fare + Base Fare\*5% = 50+5=55

Commission = Base Price\*5% = 5

Tax = Fixed\*5% = 5.50

Total = ($Fixed + $extracharges + $taxAmount) = 55+0+5.50=60.50




| Account    | Debit   | Credit | Note             |
| -----------|:-------:|:------:|-----------------:|
| Admin      |         | 5.00   | admin commission |
| Provider   | 5.00    |        | admin commission |




| Account    | Debit   | Credit | Note                |
| -----------|:-------:|:------:|--------------------:|
| Admin      |         | 5.50   | tax amount credited |
| Provider   | 5.50    |        | tax amount debited  |


### HOURLY Example


BP +(PM * TM)

Base Fare=50

Commission=10%

Per Minute = $1

Total Minutes = 2

Minute=Base Fare+(Per Minute\*Total Minutes)=50+(1\*2)=50+2=52

Tax=10%

Hour Price=Total Minutes\*2 = $2



Base Price = Base Fare + Base Fare\*5% = 50+5=55

Commission = Base Price\*5% = 5

Tax = Fixed\*5% = 5.50

Total = ($Fixed + $extracharges + $taxAmount) = 55+0+5.50=60.50


fixed=57.20

minute=52

commission=5.20

tax=5.72

total=62.92
