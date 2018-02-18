<?php die("Access Denied"); ?>#x#a:2:{s:6:"result";a:5:{i:0;O:8:"stdClass":3:{s:4:"link";s:101:"http://virtuemart.net/news/488-virtuemart-and-the-new-french-financial-law-valid-since-january-1-2018";s:5:"title";s:71:"VirtueMart and the new french financial law valid since January 1, 2018";s:11:"description";s:455:"<p>The purpose of the french financial law n&deg; <a href="https://www.legifrance.gouv.fr/eli/loi/2017/12/30/CPAX1723900L/jo/texte/#JORFARTI000036339313" target="_blank" rel="noopener noreferrer">2017-1837</a> is to combat VAT fraud. Since January 1, 2018, it obliges French ecommerce websites to use an extension that meets the requirements of inalterability, security, preservation and archiving of data for control of the french tax administration.</p>";}i:1;O:8:"stdClass":3:{s:4:"link";s:51:"http://virtuemart.net/news/487-just-a-hotfix-update";s:5:"title";s:40:"VirtueMart 3.2.10 - Just a hotfix update";s:11:"description";s:814:"<p>Just a hotfix update.</p>
<div>
<p><a href="http://virtuemart.net/download">DOWNLOAD VM3 NOW<br> VirtueMart 3 component (core and AIO)</a></p>
<p>&nbsp;</p>
</div>
<p>Here is the complete list of fixes:</p>
<ul><li>PayPal: Check IPN provider IP extra config parameter for standard and hosted (disabled by default now)</li>
<li>Important fix for vmcrypt preventing creation of keys, if there is already an existing one.</li>
<li>important fix for the date, the call was accidently using "null" as timezone parameter, which returns the server time. Added parameter and replaced the null against a default "false", which uses then the joomla configuration for the Timezone.</li>
<li>category browse view, added "alreadyLoadedIds" to group product for the feature "omitt already loaded"<br><br></li>
</ul>";}i:2;O:8:"stdClass":3:{s:4:"link";s:62:"http://virtuemart.net/news/486-virtuemart-3-2-8-bugfix-release";s:5:"title";s:33:"VirtueMart 3.2.8 - Bugfix Release";s:11:"description";s:2811:"<p>Unfortunately, we were a bit too fast with our security release, having found an error in the testing phase we created another small bug while we were fixing it.</p>
<p>VirtueMart usually sets the default Joomla frontend language as the shop language, it is this function that had an issue. Some multi-lingual shops failed to load products when the shop language was not explicitly set, or not by default in english.</p>
<p>We have tested this new fix and we do not see any bugs.</p>
<p>Finally, we dropped our dependency on SimplePie for RSS feeds and now use the JFeedFactory of Joomla to display the news and product feed on the dashboard.</p>
<div>
<p><a href="http://virtuemart.net/download">DOWNLOAD VM3 NOW<br> VirtueMart 3 component (core and AIO)</a></p>
</div>
<p>Here is the complete list of fixes:</p>
<ul><li>Fixes for search options and display of search results</li>
<li>Search plugin, added SKU (by Franz-Peter Scherer)</li>
<li>Shop language is correctly set by Joomla default front end language</li>
<li>Fixed another problem with the order language</li>
<li>While loop finding a product alias got increased to 40 (was 20) to prevent errors when child products did not find a proper alias</li>
<li>Fixed broken new Coupon</li>
<li>Fixed broken displayLogos function (was missing a DS)</li>
<li>Fixed version.php revision number</li>
<li>Fixed lost sorting of product list if a product was stored</li>
<li>Uncategorized products are listed again in the admin product list</li>
<li>The fixed thumbnail size in the product list is now set to 90px</li>
<li>Added layout of customfield to customfields list</li>
<li>vmLoaderPluginUpdate. Removed buggy isClient() against isAdmin(). So vm3.2.8 should be Joomla 2.5 compatible again</li>
<li>Browsing for products of a manufacturer now activates the subordinated settings&nbsp;analogous to categories</li>
<li>Removed links in Order print view (destroyed layouts without correct css)</li>
<li>Removed ShipTo address in invoice, if the address is the same as BillTo</li>
<li>Changed RSS feed, dropping simplepie and using jfeedFactory instead, see <a href="http://forum.virtuemart.net/index.php?topic=138918.msg487976#msg487976">http://forum.virtuemart.net/index.php?topic=138918.msg487976#msg487976</a></li>
<li>fancybox/jquery.fancybox-1.3.4.pack.js got updated. Removed a little bug. See <a href="https://forum.virtuemart.de/installation-updates-einrichtung-156/fancybox-fehlerverhalten-loesung-3146/">https://forum.virtuemart.de/installation-updates-einrichtung-156/fancybox-fehlerverhalten-loesung-3146/</a></li>
<li>Fix for the router when the URL of the product uses the language fallback</li>
<li>Fix XPF currency</li>
<li>Paybox: fix min_amount, countries and check server availability new parameter</li>
</ul>";}i:3;O:8:"stdClass":3:{s:4:"link";s:94:"http://virtuemart.net/news/482-virtuemart-3-2-6-security-release-and-overhauled-infrastructure";s:5:"title";s:65:"VirtueMart 3.2.6 - Security Release and overhauled infrastructure";s:11:"description";s:4451:"<p>A minor XSS vulnerability was present in versions prior to 3.2.6. It occurred when the features feeds and search were used together. It happened only for feed enabled, so administrators can also close the leak in earlier versions by disabling the feed functions. The URL creation of the feed function used an improper call for JRoute. So urlencoded js was executed. The problem is fixed now by using our getCurrentUrlBy function, which works with a whitelist for variable names and it urlencodes any value.&nbsp;</p>
<div>
<p><a href="http://virtuemart.net/download">DOWNLOAD VM3 NOW<br> VirtueMart 3 component (core and AIO)</a></p>
</div>
<h2>Changes in VirtueMart version 3.2.6</h2>
<p>The plugin vmLoaderPluginUpdate now redirects from the normal Joomla registration to the VirtueMart registration. The reason is that the Joomla registration is always missing the address and other VirtueMart related information. So it should not be used. The plugin provides a parameter to disable it. The normal customfields of type S or M can now use the price modifier as percentage. The shipment plugin now also works with multiple countries. The media manager has a new important function, we can now delete a media physically (not just the entry) and the thumbs are also automatically deleted. Some extra security checks were added. Version 3.2.6 is not joomla 2.5 compatible anylonger.</p>
<ul><li>Important patch to prevent memory leak when switching languages.</li>
<li>usermodel, extra check if the already loaded user has the right id.</li>
<li>Renamed order_done layout to orderdone to be able to create a menu item.</li>
<li>New feature customfield of type S and M have now a new parameter, which enables the added price as percentage.</li>
<li>Added redirect per system plugin "vmLoaderPluginUpdate" for register and login.</li>
<li>Shipment plugin shows now also multiple countries.</li>
<li>vmJsApi, fix for correct language of the datepicker.</li>
<li>mediahandler has now a deleteAllThumbs of a certain image function (works with regex, may delete accidently too much thumbs which is quite likely unimportant.</li>
<li>Vendor model getVendorAddressFields does not work with internal id anylonger.</li>
<li>BE category list keeps selected category.</li>
<li>Very important fix for multivariants, which lost in some conditions the parent option, when changing to a child.</li>
<li>Language dependent caching.</li>
<li>install.sql, removed NULLs for product group booleans, like featured, discontinued, ...</li>
<li>More security for function getMyOrderDetails.</li>
<li>Enhanced search plugin.</li>
<li>Removed double // in function displayLogos in vmpsplugin.php. When the shipment/payment logo dissapeared in checkout, please read&nbsp;http://forum.virtuemart.net/index.php?topic=138927.0</li>
<li>Function changeShopper, address is not pre-filled with userdata of the switching user (in case the address is not provided).</li>
<li>Fixed frontend manager link permission in user accountmaintenance.</li>
</ul><p>You can find the full list of changes here:&nbsp;http://forum.virtuemart.net/index.php?topic=138912.0</p>
<h2>Changes on the VirtueMart Website</h2>
<p>A task force led by Stefan Schumacher finally updated our virtuemart.net site to joomla 3.8. Lately a lot of people also noticed the trouble with our SSL certificate, issued by StartCom. Initially, Google had announced to revoke trust for certificates issued by StartCom after October 21, 2016. Our expensive wildcard certificate was issued before that date, so there was no need for action. Unfortunately Google actually phased out trust also for all older WoSign and StartCom certificates with the release of Chrome 61. If you want to read more details about this, have a look at <br>https://wiki.mozilla.org/CA:WoSign_Issues.<br>&nbsp;<br>So we decided to use Let'sEncrypt instead, which runs maintenance free only with Certbot installed. This led to the problem that we had to update our main server completely. For this, we received fantastic help by S&ouml;ren Eberhardt-Biermann, the founder of VirtueMart. All systems are finally updated and running with the latest versions. This means for dev.virtuemart.net that we now operate with the latest redmine version and that our SVN server got updated, too. The mail server system has also been updated, because the old system sometimes had hiccups. Last but not least we also updated to php7.</p>";}i:4;O:8:"stdClass":3:{s:4:"link";s:81:"http://virtuemart.net/news/481-virtuemart-3-2-4-released-with-new-payment-options";s:5:"title";s:50:"VirtueMart 3.2.4 released with new payment options";s:11:"description";s:3738:"<p>The new version comes with a slightly improved PayPal plugin and a new PayPal product named "PayPal Credit". It allows to finance a purchase with PayPal's partner Comenity Capital Bank.</p>
<p>Furthermore Amazon Pay is now ready for productive use. It makes the cart more efficient by using the same login as for amazon.com which autofills the customer's address into the VirtueMart BillTo and ShipTo address forms.</p>
<div>
<p><a href="http://virtuemart.net//download">DOWNLOAD VM3 NOW<br> VirtueMart 3 component (core and AIO)</a></p>
</div>
<p>Also, we worked hard to improve PHP 7 compatibility, exchanged volatile loops and conditions for more robust code and added more error handling code to prevent breaking javascripts. New abstract language helper functions now create the SQL for the language fallbacks, thus reducing bugs and increasing consistent behaviour. The backend now provides more functions for a comfortable workflow, more tooltips, filters, stored states of filters, fixed links and small GUI enhancements. Editing an order now calculates proper results. For example, changing the order status for one item now updates the inventory correctly.</p>
<p>The whole 'Tools' section got cleaned up and a more logical layout. We added a new wizard for setting the safepath, which sets a secure safepath with one click. The old function to change the storeowner got enhanced and now works reliable even when the vmuser entry is missing.<br>The revenue report now works correctly to the second. There is also a new hidden config to set the mode for the week.</p>
<p>The new joomla core 3.7.4 creates the plugin object while updating, so updating a VirtueMart plugin ends in a fatal error because the VM plugins need the loaded VirtueMart environment. Therefore we added a small system plugin, which ensures that the vm environment is loaded.</p>
<p>New triggers increase the flexibility of VirtueMart. The triggers plgVmBeforeStoreProduct and plgVmAfterStoreProduct in the product model allows automatically set product properties. The triggers plgVmOnUpdateCart (in cart controller) and plgVmOnAddToCart (in cart helper function add) give programmers more control when a user is adding an item to the cart.</p>
<p>The cart also has been enhanced with new features. We now have the intuitive automatic shipment/payment. The old method was to set a shipment/payment automatically, when there was only one choice. Then we added a small javascript, which sets the configured method automatically, when available. This had the disadvantage that only one method could be automatically configured and when it was not available, nothing happened. The new method automatically sets the first method. The item update within the cart now also uses ajax, except for removing a product, because there was no backward compatible solution (we may find one later). The new cart layout does not show an extra 'Save' button for the shipment/payment selection anylonger. Plugins which provide extra data must add the button themself.</p>
<p>Opening the order details now works also with ajax. Ajax for the category browse view currently is too complex considering backward compatibility, but it is of course planned for the future. The new productdetails layout now uses the thumbnail function to display the main image. This sounds a bit strange at first, but at the end it makes the automatic resizing feature also available for the main images. Layout overriders can now also change the used layout for the order list and order detail views per hidden config.</p>
<p>To read the complete change list <a href="http://forum.virtuemart.net/index.php?topic=137816.0">http://forum.virtuemart.net/index.php?topic=137816.0</a>
</p>";}}s:6:"output";s:0:"";}