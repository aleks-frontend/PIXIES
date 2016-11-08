/* WT-Frontend v1.4 by Martin Nielsen http://jirc.webt.dk
No need to change anything in this file
*/

away = 'no';
if (document.images) {
	away_1 = new Image
	away_2 = new Image
	away_1.src = 'gfx/setaway.gif'
	away_2.src = 'gfx/setback.gif'
}
function setmsg (type) {
	msg = prompt("Enter your "+ type +" message here:", "");
	if (msg != null) { return msg; }
	else { return false; }
}
function jpilot(type,action) {
	if (type == 'sclear') { document.jchat.processJInput('/clear '); }
	if (type == 'time') { document.jchat.processJInput('/ctcp %$HN time'); }
	if (type == 'version') { document.jchat.processJInput('/ctcp %$HN version'); }
	if (type == 'whois') { document.jchat.processJInput('/whois %$HN '); }
	if (type == 'ping') { document.jchat.processJInput('/ctcp %$HN ping'); }
	if (type == 'memoserv') {
		if (action == 'send') {
			to = prompt('Who to send to?','');
			if (to != null) {
				msg = prompt('Your msg to '+to+' ?','');
				if (msg != null) {
	
				 		document.jchat.processJInput('/msg memoserv send '+to +' '+msg ); 
				}
			
			}
		}
		if (action == 'cancel') {
			to = prompt('Cancel memo to?','');
			if (to != null) {
		 		document.jchat.processJInput('/msg memoserv cancel '+to ); 		
			}
		}
		if (action == 'list') { document.jchat.processJInput('/msg memoserv list'); }
		if (action == 'readlast') { document.jchat.processJInput('/msg memoserv read last'); }
		if (action == 'read') {
			num = prompt('Memo to read?','1');
			if (num != null) {
		 		document.jchat.processJInput('/msg memoserv read '+num ); 		
			}
		}
		if (action == 'delete') {
			num = prompt('Memo to delete?','1');
			if (num != null) {
		 		document.jchat.processJInput('/msg memoserv del '+num ); 		
			}
		}
		if (action == 'delall') {
			doit = confirm('This will delete all saved memos are you sure?');
			if (doit != false) {
		 		document.jchat.processJInput('/msg memoserv del all'); 		
			}
		}
		if (action == 'setnotify') {
			when = prompt('When do you want to be notifyed that you have a new memo?\n options are: ON/LOGON/NEW/OFF','ON');
			if (when != null) {
		 		document.jchat.processJInput('/msg memoserv set notify '+when ); 		
			}
		}
		if (action == 'setlimit') {
			max = prompt('Max number of memos to recieve? max 20','20');
			if (max != null) {
				if (max <= '20') {
			 		document.jchat.processJInput('/msg memoserv set limit '+max ); 		
				}
				else { alert('You can set this to a maximum of 20'); }
			}
		}

	}
	if (type == 'chanserv') {
		if (action == 'register') {
			pass = prompt('Enter password','');
			if (pass != null) {
				description = prompt('Description of your channel','My chan');
				if (description != null) {
					document.jchat.processJInput('/msg chanserv register #%$C '+ pass +' '+description);				
				}
			}
		}
		if (action == 'identify') {
			pass = prompt('Enter password','');
			if (pass != null) {
				document.jchat.processJInput('/msg chanserv identify #%$C '+ pass );
			}			
		}
		if (action == 'drop') {
			doit = confirm('This will drop the registration of this channel are you sure?');
			if (doit != false) {
		 		document.jchat.processJInput('/msg chanserv DROP #%$C '); 		
			}		
		}
		if (action == 'accesslist') {
			document.jchat.processJInput('/msg chanserv access #%$C list'); 
		}
		if (action == 'add') {
			nickname = prompt('enter nickname to add, must be a registered nickname','');
			if (nickname != null) {
				level = prompt('Enter level:\n 3 = voice\n4 = halfop\n5 = op');
				if (level != null) {
					document.jchat.processJInput('/msg chanserv access #%$C add '+nickname +' '+level); 
				}
			}
		}
		if (action == 'del') {
			nickname = prompt('enter nickname to delete','');
			if (nickname != null) {
				document.jchat.processJInput('/msg chanserv access #%$C del '+nickname ); 
			}
		}

	}

	if (type == 'botserv') {
		if (action == 'list') {
			document.jchat.processJInput('/msg botserv botlist'); 
		}
		if (action == 'assign') {
			botnick = prompt('Enter botname to assign remember to do list bots to see avaliable bots','Atomiccat');
			if (botnick != null) {
				document.jchat.processJInput('/msg botserv assign #%$C '+botnick ); 
			}
		}
		if (action == 'inassign') {
			botnick = prompt('Enter botname to unassign','');
			if (botnick != null) {
				document.jchat.processJInput('/msg botserv unassign #%$C '+botnick ); 
			}
		}

	}

	if (type == 'join') { 
		if (document.jform.channels.indexOf != '0') {
			if (document.jform.channels.options[document.jform.channels.selectedIndex].value != 'other') {
			document.jchat.processJInput('/join '+ document.jform.channels.options[document.jform.channels.selectedIndex].value);
			return true;
			}
			else {
				chan = prompt('Channel to join','#mychannel');
				if (chan != null) {
					if (chan.indexOf('#') != -1 ) { chan = '/join '+chan; }
						else { chan = '/join #'+chan; }
				 		document.jchat.processJInput(chan); 
				 }
			}
		}
		document.jform.channels.selectedIndex = 0;
	}
		if (type == 'actions') { 
			if (document.jform.actions.selectedIndex != '0') {
				if (document.jform.actions.options[document.jform.actions.selectedIndex].value != 'other') {
					document.jchat.processJInput('/me '+ document.jform.actions.options[document.jform.actions.selectedIndex].value)
				}
			else {
				action = prompt('Action to perform','Smiles innocently');
				if (action != null) {
					action = '/me '+action;
				 	document.jchat.processJInput(action); 
				 }
			}
		}
		document.jform.actions.selectedIndex = 0;
	}
	if (type == 'limit') {
				action = prompt('Set limit to this, leave blank to unset','');
				if (action != null) {
					if (action != '') {
						action = '/mode #%$C +l '+action;
				 		document.jchat.processJInput(action); 
				 	}
				 	else { document.jchat.processJInput('/mode #%$C -l')  }
				}
			}
	if (type == 'invite') {
			nick = prompt('Invite this person to channel','');
			if (nick != null) {
				if (nick != '') {
		 			document.jchat.processJInput('/invite '+nick +' #%$C '); 
				}
			}
	}

	if (type == 'opmode') {
		document.jchat.processJInput('/mode #%$C '+ action +' %$HN ');
	}
	if (type == 'cmode') {
		document.jchat.processJInput('/mode #%$C '+ action );
	}
	
	if (type == 'kick') {
		if (action == 'fast') { kickmsg = 'get out of here'; }
			else { kickmsg = setmsg('kick'); }
			document.jchat.processJInput('/kick #%$C %$HN '+ kickmsg);
	}
	if (type == 'kickban') {
		kickmsg = setmsg('kick');
		if (kickmsg != false) {
			document.jchat.processJInput('/mode #%$C +b %$HN ');
			document.jchat.processJInput('/kick #%$C %$HN '+ kickmsg);
		}
	}
	if (type == 'away') {
		if (away == 'yes') { action = 'back' }
		if (away == 'no') { action = 'away' }
		if (action == 'away') {
			awaymsg = setmsg('away');
			if (awaymsg != false) {
				document.jchat.processJInput('/away ' + awaymsg);
				document.jchat.processJInput('/me is AWAY \('+awaymsg+'\).');
				away = 'yes';
				document.away.src=away_2.src
			}
		}
			else {
				document.jchat.processJInput('/away');
				document.jchat.processJInput('/me is BACK from being AWAY \('+awaymsg+'\).');
				away = 'no';
				document.away.src=away_1.src
			}
		}
	if (type == 'nickserv') {
		if (action == 'register') {
			nickpass = prompt('Enter your password followed by a space then enter your email address','');
			if (nickpass != null) {
				document.jchat.processJInput('/msg nickserv register '+ nickpass);
			}
		}
		if (action == 'identify') {
			nickpass = prompt('Enter your password','');
			document.jchat.processJInput('/msg nickserv identify '+ nickpass);
		}
		if  (action == 'ghost') {
			nickpass = prompt('Enter your password','');
			if (nickpass != null) {
				nick = prompt('Enter your nickname to ghost','');
					if (nick != null) {
						document.jchat.processJInput('/msg nickserv ghost '+ nick +' ' +nickpass);
					}
			}
		}
		if  (action == 'recover') {
			nickpass = prompt('Enter your password','');
			if (nickpass != null) {
				nick = prompt('Enter your nickname to recover','');
				if (nick != null) {
					document.jchat.processJInput('/msg nickserv recover '+ nick +' ' +nickpass);
				}
			}
		}
		if  (action == 'release') {
			nickpass = prompt('Enter your password','');
			if (nickpass != null) {
				nick = prompt('Enter your nickname to release','');
				if (nick != null) {
					document.jchat.processJInput('/msg nickserv release '+ nick + ' ' +nickpass);
				}
			}
		}
		if  (action == 'drop') {
			doit = confirm('This will drop the registration of your nickname are you sure?');
			if (doit != false) {
				document.jchat.processJInput('/msg nickserv drop ');
			}
		}
		if  (action == 'setkill') {
			kill = prompt('Set kill on/off','on');
			if (kill != null) {
				document.jchat.processJInput('/msg nickserv set kill '+ kill );
			}
		}
		if  (action == 'setsecure') {
			kill = prompt('Set secure on/off','on');
			if (kill != null) {
				document.jchat.processJInput('/msg nickserv set secure '+ kill );
			}
		}				
		if  (action == 'setpassword') {
			oldpass = prompt ('enter your current password','');
			if (oldpass != null) {
				newpass = prompt ('enter new password','');
				if (newpass != null) {
					document.jchat.processJInput('/msg nickserv set password '+ oldpass +' '+ newpass );
				}
			}
		}
	}

}