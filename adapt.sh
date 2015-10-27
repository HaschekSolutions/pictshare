cd originals/
for f in *
do
 echo "Processing $f"

 mkdir ../upload/$f
 cp $f ../upload/$f/$f
done
