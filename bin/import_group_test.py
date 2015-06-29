from omero.gateway import BlitzGateway
from omero.cli import CLI

USER='demo01'
PASSWD='Dem0o1'
HOST='vbox.omero'
PORT=4064

FNAME = '/export/hrm_data/demo01/dst/cairn_0123456789abc_hrm.tif'
GROUP_DEFAULT = 'TestGroup1'
GROUP_OTHER = 'TestGroup2'
DS_DEFAULT = '51'
DS_OTHER = '101'


print('OMERO connection details: %s@%s:%i\n' % (USER, HOST, PORT))

conn = BlitzGateway(USER, PASSWD, host=HOST, port=PORT)
conn.connect()
# conn.SERVICE_OPTS.setOmeroGroup(84)

cli = CLI()
cli.loadplugins()
cli._client = conn.c


def import_args(dsid, auth=False, grp=None):
    args = []
    if auth:
        args.extend(['-s', HOST, '-u', USER, '-w', PASSWD])
    args.extend(['import'])
    if grp is not None:
        args.extend(['-g', grp])
    args.extend(['-d', dsid])
    args.extend([FNAME])
    cli.invoke(args)
    print('\nused import args: %s' % args)


## FAILS SOMETIMES: DS_DEFAULT is in the defaul group, so this works:
# import_args(DS_DEFAULT, auth=True, grp=None)

## WORKS
import_args(DS_DEFAULT, auth=True, grp=GROUP_DEFAULT)

# # fails
# import_args(DS_OTHER, auth=False, grp=GROUP_OTHER)
# 
# works
import_args(DS_OTHER, auth=True, grp=GROUP_OTHER)
import_args(DS_DEFAULT, auth=True, grp=GROUP_DEFAULT)
# 
# # DS_DEFAULT is in the defaul group, so this works:
# import_args(DS_DEFAULT, auth=False, grp=None)
