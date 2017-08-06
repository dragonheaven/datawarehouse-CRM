#!/bin/bash
mkdir /usr/share/GeoIP/
cd /usr/share/GeoIP/
rm GeoIPCity.dat
wget http://geolite.maxmind.com/download/geoip/database/GeoLiteCity.dat.gz
gunzip GeoLiteCity.dat.gz
mv GeoLiteCity.dat GeoIPCity.dat
rm GeoIPASNum.dat
wget http://download.maxmind.com/download/geoip/database/asnum/GeoIPASNum.dat.gz
gunzip GeoIPASNum.dat.gz