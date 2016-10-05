Name:		nethvoice-module-nethcqr
Version: 1.0.4
Release: 1%{?dist}
Summary:	NethCQR module for Nethvoice
Group:		Networking/Daemons	
License:	GPL	
Source0:	%{name}-%{version}.tar.gz
BuildRoot:	%(mktemp -ud %{_tmppath}/%{name}-%{version}-%{release}-XXXXXX)
BuildArch:	noarch
BuildRequires: nethserver-devtools	
BuildRequires: gettext
Requires: nethserver-nethvoice
Requires: nethserver-nethvoice-enterprise


%description
NethCQR module for Nethvoice


%prep
%setup 


%build
for PO in $(find root -name "*\.po" | grep 'i18n\/it_IT')
    do msgfmt -o $(echo ${PO} | sed 's/\.po$/.mo/g') ${PO}
    /bin/rm ${PO}
done
perl createlinks
#php license substitution 
find root/ -type f | xargs \
    sed -i 's/\/\/PHPLICENSE/\/\/\n\/\/ Copyright (C) 2010-2015 Nethesis s.r.l. - All rights reserved. \n\/\//g'
#bash license substitution 
find root/ -type f | xargs \
    sed -i 's/#BASHLICENSE/##\n##Copyright (C) 2010-2015 Nethesis s.r.l. - All rights reserved. \n##/g'
#mysql license substitution
find root/ -type f | xargs \
    sed -i 's/--MySQLLICENSE/--\n-- Copyright (C) 2010-2015 Nethesis s.r.l. - All rights reserved. \n--/g'
#ini license substitution
find root/ -type f | xargs \
    sed -i 's/;INILICENSE/;\n; Copyright (C) 2010-2015 Nethesis s.r.l. - All rights reserved. \n;/g'

%install
rm -rf $RPM_BUILD_ROOT
(cd root ; find . -depth -print | cpio -dump $RPM_BUILD_ROOT)


/sbin/e-smith/genfilelist --dir /var/lib/asterisk/agi-bin 'attr(0775,asterisk,asterisk)' \
--dir /var/lib/asterisk 'attr(0755,asterisk,asterisk)' \
$RPM_BUILD_ROOT > %{name}-%{version}-filelist



%clean
rm -rf $RPM_BUILD_ROOT

%files -f %{name}-%{version}-filelist
%defattr(-,asterisk,asterisk)
%dir %{_nseventsdir}/nethvoice-module-nethcqr-update

%changelog
* Wed Dec 16 2015 Stefano Fancello <stefano.fancello@nethesis.it> - 1.0.4-1
- Fix destination description to avoid freepbx dashbord notices. Refs #3921

* Wed Mar 11 2015 Stefano Fancello <stefano.fancello@nethesis.it> - 1.0.3-1.ns6
- added Licenses in files
- added missing columns to default database
- Added requires for nethserver-nethvoice-enterprise package to check 

* Wed Nov 12 2014 Stefano Fancello <stefano.fancello@nethesis.it> - 1.0.2-1.ns6
- First NethVoice NG release


